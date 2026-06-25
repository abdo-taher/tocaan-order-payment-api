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
    | 3. Add credentials in the 'credentials' section (or use .env)
    |
    */

    'gateways' => [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PaypalGateway::class,
        'cash' => CashGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Credentials
    |--------------------------------------------------------------------------
    |
    | Per-gateway configuration. Each gateway can read its credentials here.
    | Use .env variables to keep secrets out of version control.
    |
    */

    'credentials' => [
        'credit_card' => [
            'merchant_id' => env('CREDIT_CARD_MERCHANT_ID'),
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'api_secret' => env('CREDIT_CARD_API_SECRET'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],
        'cash' => [],
    ],

];
