<?php

namespace Tests\Unit\Services;

use App\DTOs\ProcessPaymentDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
        $this->user = User::factory()->create();
    }

    private function createConfirmedOrder(float $total): Order
    {
        $order = Order::factory()->for($this->user)->confirmed()->create(['total' => $total]);
        OrderItem::factory()->for($order)->create([
            'quantity' => 1,
            'price' => $total,
            'subtotal' => $total,
        ]);

        return $order;
    }

    public function test_process_payment_succeeds_for_confirmed_order(): void
    {
        $order = $this->createConfirmedOrder(100.00);

        $dto = new ProcessPaymentDTO(
            orderId: $order->id,
            amount: 100.00,
            method: 'credit_card',
        );

        $payment = $this->paymentService->processPayment($dto);

        $this->assertEquals(PaymentStatus::Successful, $payment->status);
        $this->assertNotNull($payment->transaction_id);
    }

    public function test_process_payment_throws_for_pending_order(): void
    {
        $order = Order::factory()->for($this->user)->create([
            'status' => OrderStatus::Pending,
            'total' => 100.00,
        ]);

        $dto = new ProcessPaymentDTO(
            orderId: $order->id,
            amount: 100.00,
            method: 'credit_card',
        );

        $this->expectException(ValidationException::class);
        $this->paymentService->processPayment($dto);
    }

    public function test_process_payment_throws_for_cancelled_order(): void
    {
        $order = Order::factory()->for($this->user)->cancelled()->create(['total' => 100.00]);

        $dto = new ProcessPaymentDTO(
            orderId: $order->id,
            amount: 100.00,
            method: 'credit_card',
        );

        $this->expectException(ValidationException::class);
        $this->paymentService->processPayment($dto);
    }

    public function test_process_payment_throws_if_already_paid(): void
    {
        $order = $this->createConfirmedOrder(100.00);
        Payment::factory()->successful()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
        ]);

        $dto = new ProcessPaymentDTO(
            orderId: $order->id,
            amount: 100.00,
            method: 'credit_card',
        );

        $this->expectException(ValidationException::class);
        $this->paymentService->processPayment($dto);
    }

    public function test_process_payment_throws_if_amount_mismatch(): void
    {
        $order = $this->createConfirmedOrder(100.00);

        $dto = new ProcessPaymentDTO(
            orderId: $order->id,
            amount: 50.00,
            method: 'credit_card',
        );

        $this->expectException(ValidationException::class);
        $this->paymentService->processPayment($dto);
    }

    public function test_process_payment_throws_for_nonexistent_order(): void
    {
        $dto = new ProcessPaymentDTO(
            orderId: 9999,
            amount: 100.00,
            method: 'credit_card',
        );

        $this->expectException(ValidationException::class);
        $this->paymentService->processPayment($dto);
    }

    public function test_get_payment_for_order_returns_payment(): void
    {
        $order = $this->createConfirmedOrder(100.00);
        Payment::factory()->successful()->create(['order_id' => $order->id]);

        $payment = $this->paymentService->getPaymentForOrder($order->id);

        $this->assertNotNull($payment);
        $this->assertEquals($order->id, $payment->order_id);
    }

    public function test_get_payment_for_order_returns_null_when_none(): void
    {
        $order = Order::factory()->for($this->user)->create();

        $payment = $this->paymentService->getPaymentForOrder($order->id);

        $this->assertNull($payment);
    }
}
