<?php

namespace App\Http\Controllers;

use App\DTOs\ProcessPaymentDTO;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentCollection;
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
     * List all payments for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->listPayments(
            userId: (int) $request->user()->id,
            perPage: (int) $request->query('per_page', 15),
        );

        return $this->paginated(new PaymentCollection($payments), 'messages.payments.retrieved');
    }

    /**
     * Process a payment for an order.
     */
    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        $dto = ProcessPaymentDTO::fromArray($request->validated());

        $payment = $this->paymentService->processPayment($dto);

        if ($payment->status->value === 'successful') {
            return $this->created(new PaymentResource($payment), 'messages.payments.processed');
        }

        return $this->error('messages.payments.failed', 422);
    }

    /**
     * Get payment details for an order.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $payment = $this->paymentService->getPaymentForOrder($orderId);

        if (!$payment) {
            return $this->notFound('messages.payments.not_found');
        }

        return $this->success(new PaymentResource($payment), 'messages.payments.retrieved');
    }
}
