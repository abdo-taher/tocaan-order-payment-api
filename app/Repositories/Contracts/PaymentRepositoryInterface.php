<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    /**
     * Get paginated payments for a user.
     */
    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

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
