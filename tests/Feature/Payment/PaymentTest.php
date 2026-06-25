<?php

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    private function createConfirmedOrderWithTotal(float $total): Order
    {
        $order = Order::factory()->for($this->user)->confirmed()->create(['total' => $total]);
        OrderItem::factory()->for($order)->create([
            'quantity' => 1,
            'price' => $total,
            'subtotal' => $total,
        ]);

        return $order;
    }

    // ─── Process Payment ────────────────────────────────────────────────

    public function test_can_process_payment_for_confirmed_order_with_credit_card(): void
    {
        $order = $this->createConfirmedOrderWithTotal(100.00);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'method' => 'credit_card',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.method', 'credit_card')
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'order_id', 'amount', 'method', 'status', 'transaction_id'],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'successful',
        ]);
    }

    public function test_can_process_payment_with_paypal(): void
    {
        $order = $this->createConfirmedOrderWithTotal(50.00);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 50.00,
            'method' => 'paypal',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.method', 'paypal');
    }

    public function test_can_process_payment_with_cash(): void
    {
        $order = $this->createConfirmedOrderWithTotal(75.00);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 75.00,
            'method' => 'cash',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.method', 'cash');
    }

    public function test_cannot_pay_for_pending_order(): void
    {
        $order = Order::factory()->for($this->user)->create([
            'status' => OrderStatus::Pending,
            'total' => 100.00,
        ]);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'method' => 'credit_card',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_cannot_pay_for_cancelled_order(): void
    {
        $order = Order::factory()->for($this->user)->cancelled()->create(['total' => 100.00]);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'method' => 'credit_card',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_cannot_pay_if_already_has_successful_payment(): void
    {
        $order = $this->createConfirmedOrderWithTotal(100.00);

        Payment::factory()->successful()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
        ]);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'method' => 'credit_card',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_payment_amount_must_match_order_total(): void
    {
        $order = $this->createConfirmedOrderWithTotal(100.00);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 50.00,
            'method' => 'credit_card',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_payment_fails_with_invalid_method(): void
    {
        $order = $this->createConfirmedOrderWithTotal(100.00);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'method' => 'bitcoin',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['method']);
    }

    public function test_payment_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/payments', [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id', 'amount', 'method']);
    }

    // ─── View Payment ───────────────────────────────────────────────────

    public function test_can_view_payment_for_order(): void
    {
        $order = $this->createConfirmedOrderWithTotal(100.00);
        Payment::factory()->successful()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}/payment", $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'order_id', 'amount', 'method', 'status', 'transaction_id'],
            ]);
    }

    public function test_returns_404_when_no_payment_exists(): void
    {
        $order = Order::factory()->for($this->user)->create();

        $response = $this->getJson("/api/orders/{$order->id}/payment", $this->authHeaders());

        $response->assertStatus(404);
    }

    // ─── Auth Guard ─────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_process_payment(): void
    {
        auth()->logout();

        $response = $this->postJson('/api/payments', [
            'order_id' => 1,
            'amount' => 100.00,
            'method' => 'credit_card',
        ]);

        $response->assertStatus(401);
    }
}
