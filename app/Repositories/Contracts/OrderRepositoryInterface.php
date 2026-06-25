<?php

namespace App\Repositories\Contracts;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * Get paginated orders for a user, optionally filtered by status.
     */
    public function paginateByUser(int $userId, ?OrderStatus $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order;

    /**
     * Find an order by ID that belongs to a specific user.
     */
    public function findByIdForUser(int $id, int $userId): ?Order;

    /**
     * Create a new order.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Order;

    /**
     * Update an order.
     *
     * @param array<string, mixed> $data
     */
    public function update(Order $order, array $data): Order;

    /**
     * Delete an order.
     */
    public function delete(Order $order): bool;
}
