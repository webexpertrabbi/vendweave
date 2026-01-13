<?php

namespace VendWeave\Gateway\Tests\Unit;

use VendWeave\Gateway\Services\PaymentManager;
use VendWeave\Gateway\Services\TransactionVerifier;
use VendWeave\Gateway\Services\VerificationResult;
use VendWeave\Gateway\Tests\TestCase;
use Mockery;

class PaymentManagerTest extends TestCase
{
    public function test_verify_returns_invalid_method_for_unsupported_method(): void
    {
        $verifier = Mockery::mock(TransactionVerifier::class);
        $manager = new PaymentManager($verifier);

        $result = $manager->verify('ORDER-1', 100.00, 'invalid_method');

        $this->assertTrue($result->isFailed());
        $this->assertEquals('INVALID_PAYMENT_METHOD', $result->getErrorCode());
    }

    public function test_get_payment_methods_returns_configured_methods(): void
    {
        $verifier = Mockery::mock(TransactionVerifier::class);
        $manager = new PaymentManager($verifier);

        $methods = $manager->getPaymentMethods();

        $this->assertContains('bkash', $methods);
        $this->assertContains('nagad', $methods);
        $this->assertContains('rocket', $methods);
        $this->assertContains('upay', $methods);
    }

    public function test_is_valid_payment_method_returns_true_for_valid_method(): void
    {
        $verifier = Mockery::mock(TransactionVerifier::class);
        $manager = new PaymentManager($verifier);

        $this->assertTrue($manager->isValidPaymentMethod('bkash'));
        $this->assertTrue($manager->isValidPaymentMethod('BKASH')); // Case insensitive
        $this->assertTrue($manager->isValidPaymentMethod('nagad'));
    }

    public function test_is_valid_payment_method_returns_false_for_invalid_method(): void
    {
        $verifier = Mockery::mock(TransactionVerifier::class);
        $manager = new PaymentManager($verifier);

        $this->assertFalse($manager->isValidPaymentMethod('paypal'));
        $this->assertFalse($manager->isValidPaymentMethod('mastercard'));
    }

    public function test_verify_calls_verifier_with_normalized_method(): void
    {
        $verifier = Mockery::mock(TransactionVerifier::class);
        $verifier->shouldReceive('verify')
            ->once()
            ->with('ORDER-1', 100.00, 'bkash', null)
            ->andReturn(VerificationResult::pending());

        $manager = new PaymentManager($verifier);
        $result = $manager->verify('ORDER-1', 100.00, 'BKASH');

        $this->assertTrue($result->isPending());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
