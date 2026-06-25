<?php

namespace App\Http\Controllers;

use App\DTOs\StoreOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Enums\OrderStatus;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * List orders with optional status filter and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->listOrders(
            userId: (int) $request->user()->id,
            status: $request->query('status'),
            perPage: (int) $request->query('per_page', 15),
        );

        return $this->paginated(new OrderCollection($orders), 'messages.orders.retrieved');
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $dto = StoreOrderDTO::fromArray([
            'user_id' => (int) $request->user()->id,
            'items' => $request->validated('items'),
        ]);

        $order = $this->orderService->createOrder($dto);

        return $this->created(new OrderResource($order), 'messages.orders.created');
    }

    /**
     * Show a single order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return $this->notFound('messages.orders.not_found');
        }

        return $this->success(new OrderResource($order), 'messages.orders.shown');
    }

    /**
     * Update an order's items.
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return $this->notFound('messages.orders.not_found');
        }

        $dto = UpdateOrderDTO::fromArray($request->validated());
        $updatedOrder = $this->orderService->updateOrder($order, $dto);

        return $this->success(new OrderResource($updatedOrder), 'messages.orders.updated');
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return $this->notFound('messages.orders.not_found');
        }

        $newStatus = OrderStatus::from($request->validated('status'));
        $updatedOrder = $this->orderService->updateStatus($order, $newStatus);

        return $this->success(new OrderResource($updatedOrder), 'messages.orders.status_updated');
    }

    /**
     * Delete an order.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return $this->notFound('messages.orders.not_found');
        }

        $this->orderService->deleteOrder($order);

        return $this->noContent('messages.orders.deleted');
    }
}
