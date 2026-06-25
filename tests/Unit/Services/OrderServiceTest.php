<?php

namespace Tests\Unit\Services;

use App\DTOs\StoreOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
        $this->user = User::factory()->create();
    }

    public function test_create_order_creates_order_with_items_and_calculates_total(): void
    {
        $dto = new StoreOrderDTO(
            userId: $this->user->id,
            items: [
                ['product_name' => 'Item A', 'quantity' => 2, 'price' => 10.00],
                ['product_name' => 'Item B', 'quantity' => 1, 'price' => 30.00],
            ],
        );

        $order = $this->orderService->createOrder($dto);

        $this->assertEquals(OrderStatus::Pending, $order->status);
        $this->assertEquals('50.00', $order->total);
        $this->assertCount(2, $order->items);
    }

    public function test_update_order_replaces_items_and_recalculates_total(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);
        OrderItem::factory(2)->for($order)->create();
        $order->recalculateTotal();

        $dto = new UpdateOrderDTO(
            items: [
                ['product_name' => 'New Item', 'quantity' => 3, 'price' => 20.00],
            ],
        );

        $updated = $this->orderService->updateOrder($order, $dto);

        $this->assertEquals('60.00', $updated->total);
        $this->assertCount(1, $updated->items);
    }

    public function test_update_order_throws_if_not_pending(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->create();

        $dto = new UpdateOrderDTO(
            items: [['product_name' => 'X', 'quantity' => 1, 'price' => 5.00]],
        );

        $this->expectException(ValidationException::class);
        $this->orderService->updateOrder($order, $dto);
    }

    public function test_update_status_pending_to_confirmed(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);

        $updated = $this->orderService->updateStatus($order, OrderStatus::Confirmed);

        $this->assertEquals(OrderStatus::Confirmed, $updated->status);
    }

    public function test_update_status_pending_to_cancelled(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);

        $updated = $this->orderService->updateStatus($order, OrderStatus::Cancelled);

        $this->assertEquals(OrderStatus::Cancelled, $updated->status);
    }

    public function test_update_status_confirmed_to_cancelled_throws(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->create();

        $this->expectException(ValidationException::class);
        $this->orderService->updateStatus($order, OrderStatus::Cancelled);
    }

    public function test_update_status_cancelled_to_confirmed_throws(): void
    {
        $order = Order::factory()->for($this->user)->cancelled()->create();

        $this->expectException(ValidationException::class);
        $this->orderService->updateStatus($order, OrderStatus::Confirmed);
    }

    public function test_delete_order_succeeds_for_pending_without_payment(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);

        $result = $this->orderService->deleteOrder($order);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_delete_order_throws_if_not_pending(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->create();

        $this->expectException(ValidationException::class);
        $this->orderService->deleteOrder($order);
    }

    public function test_delete_order_throws_if_has_payment(): void
    {
        $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Pending]);
        $order->payment()->create([
            'amount' => 100,
            'method' => 'credit_card',
            'status' => 'successful',
        ]);

        $this->expectException(ValidationException::class);
        $this->orderService->deleteOrder($order);
    }

    public function test_list_orders_returns_only_user_orders(): void
    {
        Order::factory(3)->for($this->user)->create();
        Order::factory(5)->create(); // Other user

        $result = $this->orderService->listOrders($this->user->id);

        $this->assertEquals(3, $result->total());
    }

    public function test_list_orders_filters_by_status(): void
    {
        Order::factory(3)->for($this->user)->create(['status' => OrderStatus::Pending]);
        Order::factory(2)->for($this->user)->confirmed()->create();

        $result = $this->orderService->listOrders($this->user->id, 'confirmed');

        $this->assertEquals(2, $result->total());
    }
}
