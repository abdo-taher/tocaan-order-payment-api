<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Str;

class CashGateway implements PaymentGatewayInterface
{
    /**
     * Process a cash payment.
     *
     * @param float $amount
     * @param array<string, mixed> $details
     * @return array{success: bool, transaction_id: string|null, message: string}
     */
    public function charge(float $amount, array $details = []): array
    {
        // Cash payments are always recorded as successful.
        return [
            'success' => true,
            'transaction_id' => 'CASH_' . Str::uuid()->toString(),
            'message' => 'Cash payment recorded successfully.',
        ];
    }

    /**
     * Get the gateway name.
     */
    public function getName(): string
    {
        return 'cash';
    }
}
