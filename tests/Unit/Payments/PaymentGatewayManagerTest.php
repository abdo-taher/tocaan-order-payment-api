<?php

namespace Tests\Unit\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Gateways\CashGateway;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;
use App\Payments\PaymentGatewayManager;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    private PaymentGatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PaymentGatewayManager();
    }

    public function test_resolves_credit_card_gateway(): void
    {
        $gateway = $this->manager->resolve('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    public function test_resolves_paypal_gateway(): void
    {
        $gateway = $this->manager->resolve('paypal');

        $this->assertInstanceOf(PaypalGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    public function test_resolves_cash_gateway(): void
    {
        $gateway = $this->manager->resolve('cash');

        $this->assertInstanceOf(CashGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    public function test_throws_for_unconfigured_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment gateway [bitcoin] is not configured.');

        $this->manager->resolve('bitcoin');
    }

    public function test_available_methods_returns_configured_gateways(): void
    {
        $methods = $this->manager->availableMethods();

        $this->assertContains('credit_card', $methods);
        $this->assertContains('paypal', $methods);
        $this->assertContains('cash', $methods);
    }

    public function test_credit_card_gateway_returns_successful_charge(): void
    {
        $gateway = new CreditCardGateway();
        $result = $gateway->charge(100.00);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
        $this->assertStringStartsWith('CC_', $result['transaction_id']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_paypal_gateway_returns_successful_charge(): void
    {
        $gateway = new PaypalGateway();
        $result = $gateway->charge(50.00);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
        $this->assertStringStartsWith('PP_', $result['transaction_id']);
    }

    public function test_cash_gateway_returns_successful_charge(): void
    {
        $gateway = new CashGateway();
        $result = $gateway->charge(75.00);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
        $this->assertStringStartsWith('CASH_', $result['transaction_id']);
    }

    public function test_each_gateway_has_correct_name(): void
    {
        $this->assertEquals('credit_card', (new CreditCardGateway())->getName());
        $this->assertEquals('paypal', (new PaypalGateway())->getName());
        $this->assertEquals('cash', (new CashGateway())->getName());
    }
}
