<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    /**
     * Process a credit card payment.
     *
     * @param float $amount
     * @param array<string, mixed> $details
     * @return array{success: bool, transaction_id: string|null, message: string}
     */
    public function charge(float $amount, array $details = []): array
    {
        // In production, this would integrate with Stripe, Braintree, etc.
        // For now, simulate a successful charge.
        return [
            'success' => true,
            'transaction_id' => 'CC_' . Str::uuid()->toString(),
            'message' => 'Credit card payment processed successfully.',
        ];
    }

    /**
     * Get the gateway name.
     */
    public function getName(): string
    {
        return 'credit_card';
    }
}
