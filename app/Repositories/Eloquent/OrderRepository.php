<?php

namespace App\Repositories\Eloquent;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly Order $model
    ) {}

    /**
     * Get paginated orders for a user, optionally filtered by status.
     */
    public function paginateByUser(int $userId, ?OrderStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->with(['items']);

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order
    {
        return $this->model->newQuery()->with(['items', 'payment'])->find($id);
    }

    /**
     * Find an order by ID that belongs to a specific user.
     */
    public function findByIdForUser(int $id, int $userId): ?Order
    {
        return $this->model->newQuery()
            ->with(['items', 'payment'])
            ->where('user_id', $userId)
            ->find($id);
    }

    /**
     * Create a new order.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Order
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Update an order.
     *
     * @param array<string, mixed> $data
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh(['items', 'payment']);
    }

    /**
     * Delete an order.
     */
    public function delete(Order $order): bool
    {
        return $order->delete();
    }
}
