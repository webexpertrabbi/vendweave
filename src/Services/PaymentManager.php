<?php

namespace VendWeave\Gateway\Services;

use VendWeave\Gateway\Contracts\PaymentGatewayInterface;

/**
 * High-level payment operations manager.
 * 
 * This is the primary service exposed to Laravel applications.
 * It coordinates between the API client and transaction verifier.
 */
class PaymentManager implements PaymentGatewayInterface
{
    public function __construct(
        private readonly TransactionVerifier $verifier
    ) {}

    /**
     * Verify a transaction against the POS API.
     *
     * @param string $orderId The unique order identifier
     * @param float $amount The exact amount to verify (no tolerance)
     * @param string $paymentMethod The payment method (bkash, nagad, rocket, upay)
     * @param string|null $trxId Optional transaction ID for direct lookup
     * @return VerificationResult
     */
    public function verify(
        string $orderId,
        float $amount,
        string $paymentMethod,
        ?string $trxId = null
    ): VerificationResult {
        // Normalize payment method
        $paymentMethod = strtolower(trim($paymentMethod));

        // Validate payment method
        if (!$this->isValidPaymentMethod($paymentMethod)) {
            return VerificationResult::failed(
                'INVALID_PAYMENT_METHOD',
                "Invalid payment method: {$paymentMethod}. Supported: " . implode(', ', $this->getPaymentMethods())
            );
        }

        return $this->verifier->verify($orderId, $amount, $paymentMethod, $trxId);
    }

    /**
     * Get list of supported payment methods.
     *
     * @return array<string>
     */
    public function getPaymentMethods(): array
    {
        return config('vendweave.payment_methods', [
            'bkash',
            'nagad',
            'rocket',
            'upay',
        ]);
    }

    /**
     * Check if a payment method is valid.
     *
     * @param string $method
     * @return bool
     */
    public function isValidPaymentMethod(string $method): bool
    {
        return in_array(strtolower($method), $this->getPaymentMethods());
    }

    /**
     * Get the verification URL for an order.
     *
     * @param string $orderId
     * @return string
     */
    public function getVerifyUrl(string $orderId): string
    {
        return route('vendweave.verify', ['order' => $orderId]);
    }

    /**
     * Get payment method display information.
     *
     * @return array<string, array{name: string, color: string}>
     */
    public function getPaymentMethodsInfo(): array
    {
        return [
            'bkash' => [
                'name' => 'bKash',
                'color' => '#E2136E',
                'instructions' => 'Send money to our bKash merchant number',
            ],
            'nagad' => [
                'name' => 'Nagad',
                'color' => '#F6A623',
                'instructions' => 'Send money to our Nagad merchant number',
            ],
            'rocket' => [
                'name' => 'Rocket',
                'color' => '#8E44AD',
                'instructions' => 'Send money to our Rocket merchant number',
            ],
            'upay' => [
                'name' => 'Upay',
                'color' => '#00A651',
                'instructions' => 'Send money to our Upay merchant number',
            ],
        ];
    }
}
