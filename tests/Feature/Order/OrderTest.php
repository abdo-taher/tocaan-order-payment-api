<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─── List Orders ────────────────────────────────────────────────────

    public function test_can_list_orders_with_pagination(): void
    {
        Order::factory(20)->for($this->user)->create();

        $response = $this->getJson('/api/orders', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'user_id', 'status', 'total', 'items', 'created_at', 'updated_at']],
                'links',
                'meta',
            ]);

        $this->assertCount(15, $response->json('data'));
    }

    public function test_can_filter_orders_by_status(): void
    {
        Order::factory(3)->for($this->user)->create(['status' => OrderStatus::Pending]);
        Order::factory(2)->for($this->user)->confirmed()->create();

        $response = $this->getJson('/api/orders?status=confirmed', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_can_only_see_own_orders(): void
    {
        Order::factory(3)->for($this->user)->create();
        Order::factory(5)->create(); // other user's orders

        $response = $this->getJson('/api/orders', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_orders(): void
    {
        auth()->logout();

        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    // ─── Show Order ─────────────────────────────────────────────────────

    public function test_can_view_single_order(): void
    {
        $order = Order::factory()->for($this->user)->create();
        OrderItem::factory(2)->for($order)->create();

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'status', 'total', 'items', 'payment', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_cannot_view_other_users_order(): void
    {
        $otherOrder = Order::factory()->create();

        $response = $this->getJson("/api/orders/{$otherOrder->id}", $this->authHeaders());

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->getJson('/api/orders/9999', $this->authHeaders());

        $response->assertStatus(404);
    }

    // ─── Create Order ───────────────────────────────────────────────────

    public function test_can_create_order_with_items(): void
    {
        $payload = [
            'items' => [
                ['product_name' => 'Widget A', 'quantity' => 2, 'price' => 10.00],
                ['product_name' => 'Widget B', 'quantity' => 1, 'price' => 25.50],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', '45.50');

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Widget A',
            'quantity' => 2,
            'price' => '10.00',
            'subtotal' => '20.00',
        ]);
    }

    public function test_create_order_fails_without_items(): void
    {
        $response = $this->postJson('/api/orders', [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_create_order_fails_with_invalid_item_data(): void
    {
        $payload = [
            'items' => [
                ['product_name' => '', 'quantity' => 0, 'price' => -1],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertStatus(422);
    }

    // ─── Update Order ───────────────────────────────────────────────────

    public function test_can_update_pending_order_items(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);
        OrderItem::factory(2)->for($order)->create();

        $payload = [
            'items' => [
                ['product_name' => 'New Item', 'quantity' => 3, 'price' => 15.00],
            ],
        ];

        $response = $this->putJson("/api/orders/{$order->id}", $payload, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.total', '45.00');

        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_cannot_update_confirmed_order(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->create();

        $payload = [
            'items' => [
                ['product_name' => 'New Item', 'quantity' => 1, 'price' => 10.00],
            ],
        ];

        $response = $this->putJson("/api/orders/{$order->id}", $payload, $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_cannot_update_cancelled_order(): void
    {
        $order = Order::factory()->for($this->user)->cancelled()->create();

        $payload = [
            'items' => [
                ['product_name' => 'New Item', 'quantity' => 1, 'price' => 10.00],
            ],
        ];

        $response = $this->putJson("/api/orders/{$order->id}", $payload, $this->authHeaders());

        $response->assertStatus(422);
    }

    // ─── Update Status ──────────────────────────────────────────────────

    public function test_can_confirm_pending_order(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);

        $response = $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'confirmed'], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_can_cancel_pending_order(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);

        $response = $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'cancelled'], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_confirmed_order(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->create();

        $response = $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'cancelled'], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_cannot_confirm_cancelled_order(): void
    {
        $order = Order::factory()->for($this->user)->cancelled()->create();

        $response = $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'confirmed'], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_status_update_fails_with_invalid_status(): void
    {
        $order = Order::factory()->for($this->user)->create();

        $response = $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'invalid'], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ─── Delete Order ───────────────────────────────────────────────────

    public function test_can_delete_pending_order_without_payment(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);
        OrderItem::factory(2)->for($order)->create();

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_confirmed_order(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->create();

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_cannot_delete_order_with_payment(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::Successful,
        ]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_cannot_delete_other_users_order(): void
    {
        $otherOrder = Order::factory()->create(['status' => OrderStatus::Pending]);

        $response = $this->deleteJson("/api/orders/{$otherOrder->id}", [], $this->authHeaders());

        $response->assertStatus(404);
    }
}
