<?php

namespace VendWeave\Gateway;

use Illuminate\Support\Facades\Session;

/**
 * Helper class for common VendWeave operations.
 * 
 * Use this in your Laravel application for convenient integration.
 */
class VendWeaveHelper
{
    /**
     * Prepare order for verification and get redirect URL.
     *
     * @param string $orderId
     * @param float $amount
     * @param string $paymentMethod
     * @return string Redirect URL
     */
    public static function preparePayment(
        string $orderId,
        float $amount,
        string $paymentMethod
    ): string {
        // Store in session for verification page
        Session::put("vendweave_order_{$orderId}", [
            'amount' => $amount,
            'payment_method' => strtolower($paymentMethod),
        ]);

        return route('vendweave.verify', ['order' => $orderId]);
    }

    /**
     * Clear stored order data.
     *
     * @param string $orderId
     */
    public static function clearOrderData(string $orderId): void
    {
        Session::forget("vendweave_order_{$orderId}");
    }

    /**
     * Get the list of supported payment methods with display info.
     *
     * @return array
     */
    public static function getPaymentMethods(): array
    {
        return [
            'bkash' => [
                'name' => 'bKash',
                'color' => '#E2136E',
                'icon' => 'bkash',
            ],
            'nagad' => [
                'name' => 'Nagad',
                'color' => '#F6A623',
                'icon' => 'nagad',
            ],
            'rocket' => [
                'name' => 'Rocket',
                'color' => '#8E44AD',
                'icon' => 'rocket',
            ],
            'upay' => [
                'name' => 'Upay',
                'color' => '#00A651',
                'icon' => 'upay',
            ],
        ];
    }

    /**
     * Check if a payment method is valid.
     *
     * @param string $method
     * @return bool
     */
    public static function isValidPaymentMethod(string $method): bool
    {
        return array_key_exists(strtolower($method), self::getPaymentMethods());
    }
}
