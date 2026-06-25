<?php

namespace App\Repositories\Eloquent;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private readonly Payment $model
    ) {}

    /**
     * Get paginated payments for a user.
     */
    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->whereHas('order', fn ($query) => $query->where('user_id', $userId))
            ->with('order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new payment.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Payment
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Update a payment.
     *
     * @param array<string, mixed> $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->fresh();
    }

    /**
     * Find a payment by order ID.
     */
    public function findByOrderId(int $orderId): ?Payment
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->latest()
            ->first();
    }

    /**
     * Check if an order has a successful payment.
     */
    public function hasSuccessfulPayment(int $orderId): bool
    {
        return $this->model->newQuery()
            ->where('order_id', $orderId)
            ->where('status', PaymentStatus::Successful->value)
            ->exists();
    }
}
