<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

class PaypalGateway implements PaymentGatewayInterface
{
    /**
     * Process a PayPal payment.
     *
     * @param float $amount
     * @param array<string, mixed> $details
     * @return array{success: bool, transaction_id: string|null, message: string}
     */
    public function charge(float $amount, array $details = []): array
    {
        // In production, this would integrate with PayPal SDK.
        // For now, simulate a successful charge.
        return [
            'success' => true,
            'transaction_id' => 'PP_' . Str::uuid()->toString(),
            'message' => 'PayPal payment processed successfully.',
        ];
    }

    /**
     * Get the gateway name.
     */
    public function getName(): string
    {
        return 'paypal';
    }
}
