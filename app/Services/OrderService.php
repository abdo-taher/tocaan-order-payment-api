<?php

namespace App\Services;

use App\DTOs\StoreOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * List orders for the authenticated user with optional status filter.
     */
    public function listOrders(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $orderStatus = $status ? OrderStatus::tryFrom($status) : null;

        return $this->orderRepository->paginateByUser($userId, $orderStatus, $perPage);
    }

    /**
     * Get a single order for the authenticated user.
     */
    public function getOrder(int $orderId, int $userId): ?Order
    {
        return $this->orderRepository->findByIdForUser($orderId, $userId);
    }

    /**
     * Create a new order with items.
     */
    public function createOrder(StoreOrderDTO $dto): Order
    {
        $order = $this->orderRepository->create([
            'user_id' => $dto->userId,
            'status' => OrderStatus::Pending->value,
            'total' => 0,
        ]);

        $this->syncItems($order, $dto->items);

        return $order->load(['items']);
    }

    /**
     * Update an existing order's items.
     *
     * @throws ValidationException
     */
    public function updateOrder(Order $order, UpdateOrderDTO $dto): Order
    {
        $this->ensureOrderIsPending($order);

        if ($dto->items !== null) {
            $order->items()->delete();
            $this->syncItems($order, $dto->items);
        }

        return $order->fresh(['items', 'payment']);
    }

    /**
     * Update the status of an order.
     *
     * @throws ValidationException
     */
    public function updateStatus(Order $order, OrderStatus $newStatus): Order
    {
        $this->validateStatusTransition($order->status, $newStatus);

        $this->orderRepository->update($order, ['status' => $newStatus->value]);

        return $order->fresh(['items', 'payment']);
    }

    /**
     * Delete an order.
     *
     * @throws ValidationException
     */
    public function deleteOrder(Order $order): bool
    {
        $this->ensureOrderIsPending($order);

        if ($order->payment()->exists()) {
            throw ValidationException::withMessages([
                'order' => [__('messages.orders.has_payments')],
            ]);
        }

        return $this->orderRepository->delete($order);
    }

    /**
     * Ensure the order is in pending status.
     *
     * @throws ValidationException
     */
    private function ensureOrderIsPending(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => [__('messages.orders.only_pending_modify')],
            ]);
        }
    }

    /**
     * Validate status transitions.
     *
     * @throws ValidationException
     */
    private function validateStatusTransition(OrderStatus $current, OrderStatus $new): void
    {
        $allowed = match ($current) {
            OrderStatus::Pending => [OrderStatus::Confirmed, OrderStatus::Cancelled],
            OrderStatus::Confirmed, OrderStatus::Cancelled => [],
        };

        if (!in_array($new, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => [__('messages.orders.invalid_transition', ['from' => $current->value, 'to' => $new->value])],
            ]);
        }
    }

    /**
     * Sync items to an order and recalculate total.
     *
     * @param array<int, array{product_name: string, quantity: int, price: float}> $items
     */
    private function syncItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => round($item['quantity'] * $item['price'], 2),
            ]);
        }

        $order->recalculateTotal();
    }
}
