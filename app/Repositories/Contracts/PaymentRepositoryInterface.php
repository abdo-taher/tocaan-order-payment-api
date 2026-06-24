<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * Create a new payment.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Payment;

    /**
     * Update a payment.
     *
     * @param array<string, mixed> $data
     */
    public function update(Payment $payment, array $data): Payment;

    /**
     * Find a payment by order ID.
     */
    public function findByOrderId(int $orderId): ?Payment;

    /**
     * Check if an order has a successful payment.
     */
    public function hasSuccessfulPayment(int $orderId): bool;
}
