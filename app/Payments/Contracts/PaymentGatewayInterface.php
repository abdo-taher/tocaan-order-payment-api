<?php

namespace App\Payments\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process a payment.
     *
     * @param float $amount
     * @param array<string, mixed> $details
     * @return array{success: bool, transaction_id: string|null, message: string}
     */
    public function charge(float $amount, array $details = []): array;

    /**
     * Get the gateway name.
     */
    public function getName(): string;
}
