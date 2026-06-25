<?php

namespace App\Http\Controllers;

use App\DTOs\ProcessPaymentDTO;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Process a payment for an order.
     */
    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        $dto = ProcessPaymentDTO::fromArray($request->validated());

        $payment = $this->paymentService->processPayment($dto);

        $statusCode = $payment->status->value === 'successful'
            ? Response::HTTP_CREATED
            : Response::HTTP_UNPROCESSABLE_ENTITY;

        $message = $payment->status->value === 'successful'
            ? 'Payment processed successfully.'
            : 'Payment processing failed.';

        return response()->json([
            'message' => $message,
            'data' => new PaymentResource($payment),
        ], $statusCode);
    }

    /**
     * Get payment details for an order.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $payment = $this->paymentService->getPaymentForOrder($orderId);

        if (!$payment) {
            return response()->json([
                'message' => 'No payment found for this order.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }
}
