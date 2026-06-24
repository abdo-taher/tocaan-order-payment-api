<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve a payment gateway by method name.
     *
     * @throws InvalidArgumentException
     */
    public function resolve(string $method): PaymentGatewayInterface
    {
        $gateways = config('payment_gateways.gateways', []);

        if (!isset($gateways[$method])) {
            throw new InvalidArgumentException("Payment gateway [{$method}] is not configured.");
        }

        $gatewayClass = $gateways[$method];

        if (!class_exists($gatewayClass)) {
            throw new InvalidArgumentException("Payment gateway class [{$gatewayClass}] does not exist.");
        }

        $gateway = app($gatewayClass);

        if (!$gateway instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException(
                "Payment gateway [{$gatewayClass}] must implement PaymentGatewayInterface."
            );
        }

        return $gateway;
    }

    /**
     * Get all available payment methods.
     *
     * @return array<string>
     */
    public function availableMethods(): array
    {
        return array_keys(config('payment_gateways.gateways', []));
    }
}
