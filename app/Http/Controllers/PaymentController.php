<?php

namespace App\Http\Controllers;

use App\DTOs\ProcessPaymentDTO;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if ($payment->status->value === 'successful') {
            return $this->created(new PaymentResource($payment), 'Payment processed successfully.');
        }

        return $this->error('Payment processing failed.', 422);
    }

    /**
     * Get payment details for an order.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $payment = $this->paymentService->getPaymentForOrder($orderId);

        if (!$payment) {
            return $this->notFound('No payment found for this order.');
        }

        return $this->success(new PaymentResource($payment), 'Payment retrieved.');
    }
}
