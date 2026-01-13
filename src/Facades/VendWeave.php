<?php

namespace VendWeave\Gateway\Facades;

use Illuminate\Support\Facades\Facade;
use VendWeave\Gateway\Contracts\PaymentGatewayInterface;

/**
 * @method static \VendWeave\Gateway\Services\VerificationResult verify(string $orderId, float $amount, string $paymentMethod, ?string $trxId = null)
 * @method static array getPaymentMethods()
 * @method static bool isValidPaymentMethod(string $method)
 *
 * @see \VendWeave\Gateway\Services\PaymentManager
 */
class VendWeave extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return PaymentGatewayInterface::class;
    }
}
