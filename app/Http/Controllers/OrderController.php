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
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * List orders with optional status filter and pagination.
     */
    public function index(Request $request): OrderCollection
    {
        $orders = $this->orderService->listOrders(
            userId: (int) $request->user()->id,
            status: $request->query('status'),
            perPage: (int) $request->query('per_page', 15),
        );

        return new OrderCollection($orders);
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

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => new OrderResource($order),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Update an order's items.
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $dto = UpdateOrderDTO::fromArray($request->validated());
        $updatedOrder = $this->orderService->updateOrder($order, $dto);

        return response()->json([
            'message' => 'Order updated successfully.',
            'data' => new OrderResource($updatedOrder),
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $newStatus = OrderStatus::from($request->validated('status'));
        $updatedOrder = $this->orderService->updateStatus($order, $newStatus);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data' => new OrderResource($updatedOrder),
        ]);
    }

    /**
     * Delete an order.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id, (int) $request->user()->id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->orderService->deleteOrder($order);

        return response()->json([
            'message' => 'Order deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }
}
