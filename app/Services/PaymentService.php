<?php

namespace App\Services;

use App\DTOs\ProcessPaymentDTO;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    /**
     * Process a payment for an order.
     *
     * @throws ValidationException
     */
    public function processPayment(ProcessPaymentDTO $dto): Payment
    {
        $order = $this->orderRepository->findById($dto->orderId);

        if (!$order) {
            throw ValidationException::withMessages([
                'order_id' => [__('messages.payments.order_not_found')],
            ]);
        }

        $this->validatePaymentEligibility($order, $dto->amount);

        // Create a pending payment record
        $payment = $this->paymentRepository->create([
            'order_id' => $dto->orderId,
            'amount' => $dto->amount,
            'method' => $dto->method,
            'status' => PaymentStatus::Pending->value,
        ]);

        // Resolve the gateway and process
        $gateway = $this->gatewayManager->resolve($dto->method);
        $result = $gateway->charge($dto->amount, $dto->details);

        // Update payment based on result
        if ($result['success']) {
            $this->paymentRepository->update($payment, [
                'status' => PaymentStatus::Successful->value,
                'transaction_id' => $result['transaction_id'],
            ]);
        } else {
            $this->paymentRepository->update($payment, [
                'status' => PaymentStatus::Failed->value,
            ]);
        }

        return $payment->fresh();
    }

    /**
     * Get the payment for an order.
     */
    public function getPaymentForOrder(int $orderId): ?Payment
    {
        return $this->paymentRepository->findByOrderId($orderId);
    }

    /**
     * Validate that the order is eligible for payment.
     *
     * @throws ValidationException
     */
    private function validatePaymentEligibility(Order $order, float $amount): void
    {
        // Only confirmed orders can be paid
        if ($order->status !== OrderStatus::Confirmed) {
            throw ValidationException::withMessages([
                'order_id' => [__('messages.payments.only_confirmed')],
            ]);
        }

        // Check if already has a successful payment
        if ($this->paymentRepository->hasSuccessfulPayment($order->id)) {
            throw ValidationException::withMessages([
                'order_id' => [__('messages.payments.already_paid')],
            ]);
        }

        // Payment amount must match order total
        if (bccomp((string) $amount, (string) $order->total, 2) !== 0) {
            throw ValidationException::withMessages([
                'amount' => [__('messages.payments.amount_mismatch', ['total' => $order->total])],
            ]);
        }
    }
}
