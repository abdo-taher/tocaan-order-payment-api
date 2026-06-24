<?php

use App\Payments\Gateways\CashGateway;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Register payment gateways here. Each key is the payment method name
    | and the value is the gateway class that implements PaymentGatewayInterface.
    |
    | To add a new gateway:
    | 1. Create a class implementing App\Payments\Contracts\PaymentGatewayInterface
    | 2. Add the method_name => GatewayClass::class mapping below
    |
    */

    'gateways' => [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PaypalGateway::class,
        'cash' => CashGateway::class,
    ],

];
