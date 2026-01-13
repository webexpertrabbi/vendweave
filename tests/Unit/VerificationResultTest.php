<?php

namespace VendWeave\Gateway\Tests\Unit;

use VendWeave\Gateway\Services\VerificationResult;
use VendWeave\Gateway\Tests\TestCase;

class VerificationResultTest extends TestCase
{
    public function test_confirmed_result_is_created_correctly(): void
    {
        $result = VerificationResult::confirmed(
            trxId: 'TRX123',
            amount: 960.00,
            paymentMethod: 'bkash',
            storeId: 1
        );

        $this->assertTrue($result->isConfirmed());
        $this->assertFalse($result->isPending());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('confirmed', $result->getStatus());
        $this->assertEquals('TRX123', $result->getTrxId());
        $this->assertEquals(960.00, $result->getAmount());
        $this->assertEquals('bkash', $result->getPaymentMethod());
        $this->assertEquals(1, $result->getStoreId());
    }

    public function test_pending_result_is_created_correctly(): void
    {
        $result = VerificationResult::pending('Waiting for payment');

        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isConfirmed());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('pending', $result->getStatus());
    }

    public function test_failed_result_is_created_correctly(): void
    {
        $result = VerificationResult::failed('AMOUNT_MISMATCH', 'Amount does not match');

        $this->assertTrue($result->isFailed());
        $this->assertFalse($result->isConfirmed());
        $this->assertFalse($result->isPending());
        $this->assertEquals('failed', $result->getStatus());
        $this->assertEquals('AMOUNT_MISMATCH', $result->getErrorCode());
        $this->assertEquals('Amount does not match', $result->getErrorMessage());
    }

    public function test_already_used_result_is_created_correctly(): void
    {
        $result = VerificationResult::alreadyUsed('TRX123');

        $this->assertTrue($result->isFailed());
        $this->assertEquals('used', $result->getStatus());
        $this->assertEquals('TRX123', $result->getTrxId());
        $this->assertEquals('TRANSACTION_ALREADY_USED', $result->getErrorCode());
    }

    public function test_expired_result_is_created_correctly(): void
    {
        $result = VerificationResult::expired('TRX123');

        $this->assertTrue($result->isFailed());
        $this->assertEquals('expired', $result->getStatus());
        $this->assertEquals('TRANSACTION_EXPIRED', $result->getErrorCode());
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $result = VerificationResult::confirmed(
            trxId: 'TRX123',
            amount: 960.00,
            paymentMethod: 'bkash',
            storeId: 1
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('trx_id', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('payment_method', $array);
        $this->assertEquals('confirmed', $array['status']);
    }
}
