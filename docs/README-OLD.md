# VendWeave Gateway

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/vendweave/gateway)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011-FF2D20.svg)](https://laravel.com)

Production-grade Laravel payment gateway package for VendWeave POS integration. Supports **bKash**, **Nagad**, **Rocket**, and **Upay** payment verification.

---

## Features

- 🔐 **Secure API Authentication** - Bearer token + store secret validation
- 🏪 **Store Scope Isolation** - Transactions scoped per store
- 💰 **Exact Amount Matching** - Zero tolerance for amount discrepancies
- ⚡ **Real-time Polling** - 2.5 second polling with automatic verification
- 🎨 **Fintech-Grade UI** - Dark theme, mobile-first verification page
- 🚦 **Rate Limiting** - Built-in protection for poll endpoints
- 📝 **Detailed Logging** - All API interactions logged
- ⚠️ **Explicit Errors** - No silent failures, clear error messages

---

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
- Guzzle HTTP 7.0+

---

## Installation

Install via Composer:

```bash
composer require vendweave/gateway
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=vendweave-config
```

Optionally publish views for customization:

```bash
php artisan vendor:publish --tag=vendweave-views
```

---

## Configuration

Add the following environment variables to your `.env` file:

```env
VENDWEAVE_API_KEY=your-api-key
VENDWEAVE_API_SECRET=your-api-secret
VENDWEAVE_STORE_ID=your-store-id
VENDWEAVE_API_ENDPOINT=https://pos.vendweave.com/api

# Optional - Use sandbox for development
# VENDWEAVE_API_ENDPOINT=https://sandbox.pos.vendweave.com/api
```

### Configuration Options

```php
// config/vendweave.php

return [
    'api_key' => env('VENDWEAVE_API_KEY'),
    'api_secret' => env('VENDWEAVE_API_SECRET'),
    'endpoint' => env('VENDWEAVE_API_ENDPOINT', 'https://pos.vendweave.com/api'),
    'store_id' => env('VENDWEAVE_STORE_ID'),

    'polling' => [
        'interval_ms' => 2500,          // Poll every 2.5 seconds
        'max_attempts' => 120,          // Max 120 attempts (5 minutes)
        'timeout_seconds' => 300,       // Overall timeout
    ],

    'rate_limit' => [
        'max_attempts' => 60,           // 60 requests per minute
        'decay_minutes' => 1,
    ],

    'payment_methods' => ['bkash', 'nagad', 'rocket', 'upay'],
];
```

---

## Usage

### Basic Integration

#### 1. Store Order Data Before Redirect

```php
use Illuminate\Support\Facades\Session;

// In your checkout controller
public function checkout(Request $request)
{
    $order = Order::create([
        'amount' => $request->total,
        'payment_method' => $request->payment_method,
        // ... other order data
    ]);

    // Store order data in session for verification page
    Session::put("vendweave_order_{$order->id}", [
        'amount' => $order->total,
        'payment_method' => $order->payment_method,
    ]);

    // Redirect to VendWeave verification page
    return redirect()->route('vendweave.verify', ['order' => $order->id]);
}
```

#### 2. Or Use Query Parameters

```php
return redirect()->route('vendweave.verify', [
    'order' => $order->id,
    'amount' => $order->total,
    'payment_method' => 'bkash',
]);
```

### Using the Facade

```php
use VendWeave\Gateway\Facades\VendWeave;

// Verify a transaction manually
$result = VendWeave::verify(
    orderId: 'ORDER-123',
    amount: 960.00,
    paymentMethod: 'bkash',
    trxId: 'BKA123XYZ' // Optional
);

if ($result->isConfirmed()) {
    // Update order status
    $order->update(['status' => 'paid', 'trx_id' => $result->getTrxId()]);
}

// Check supported payment methods
$methods = VendWeave::getPaymentMethods();

// Validate a payment method
if (VendWeave::isValidPaymentMethod('bkash')) {
    // ...
}

// Get verify URL
$url = VendWeave::getVerifyUrl($orderId);
```

### Handling Verification Results

```php
use VendWeave\Gateway\Services\VerificationResult;

$result = VendWeave::verify($orderId, $amount, $method, $trxId);

switch ($result->getStatus()) {
    case VerificationResult::STATUS_CONFIRMED:
        // Payment confirmed
        $trxId = $result->getTrxId();
        $confirmedAmount = $result->getAmount();
        break;

    case VerificationResult::STATUS_PENDING:
        // Still waiting for payment
        break;

    case VerificationResult::STATUS_USED:
        // Transaction already used for another order
        $errorMessage = $result->getErrorMessage();
        break;

    case VerificationResult::STATUS_EXPIRED:
        // Transaction has expired
        break;

    case VerificationResult::STATUS_FAILED:
        // Verification failed
        $errorCode = $result->getErrorCode();
        $errorMessage = $result->getErrorMessage();
        break;
}
```

---

## Routes

The package registers the following routes:

| Method | URI                            | Name                  | Description       |
| ------ | ------------------------------ | --------------------- | ----------------- |
| GET    | `/vendweave/verify/{order}`    | `vendweave.verify`    | Verification page |
| GET    | `/vendweave/success/{order}`   | `vendweave.success`   | Success page      |
| GET    | `/vendweave/failed/{order}`    | `vendweave.failed`    | Failure page      |
| GET    | `/vendweave/cancelled/{order}` | `vendweave.cancelled` | Cancelled page    |
| GET    | `/api/vendweave/poll/{order}`  | `vendweave.poll`      | Polling endpoint  |
| GET    | `/api/vendweave/health`        | `vendweave.health`    | Health check      |

---

## Custom Callbacks

Configure custom success/failure routes:

```php
// config/vendweave.php

'callbacks' => [
    'success_route' => 'shop.order.complete',  // Your route name
    'failed_route' => 'shop.order.failed',
],
```

---

## Error Handling

The package throws specific exceptions for different error cases:

```php
use VendWeave\Gateway\Exceptions\{
    TransactionNotFoundException,
    AmountMismatchException,
    MethodMismatchException,
    StoreMismatchException,
    TransactionAlreadyUsedException,
    TransactionExpiredException,
    ApiConnectionException,
    InvalidCredentialsException
};

try {
    $result = VendWeave::verify($orderId, $amount, $method);
} catch (InvalidCredentialsException $e) {
    // API credentials not configured
} catch (ApiConnectionException $e) {
    // POS API unreachable
}
```

### Error Codes

| Code                       | Description                         |
| -------------------------- | ----------------------------------- |
| `TRANSACTION_NOT_FOUND`    | Transaction ID not found in POS     |
| `AMOUNT_MISMATCH`          | Amount doesn't match exactly        |
| `METHOD_MISMATCH`          | Payment method doesn't match        |
| `STORE_MISMATCH`           | Store scope violation               |
| `TRANSACTION_ALREADY_USED` | Transaction linked to another order |
| `TRANSACTION_EXPIRED`      | Transaction is too old              |
| `API_CONNECTION_ERROR`     | POS API unreachable                 |
| `INVALID_CREDENTIALS`      | Missing or invalid API credentials  |

---

## API Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Laravel Shop   │────▶│ VendWeave Package│────▶│  VendWeave POS  │
└─────────────────┘     └──────────────────┘     └─────────────────┘
        │                        │                        │
        │  1. Checkout           │                        │
        │─────────────────────▶  │                        │
        │                        │                        │
        │  2. Redirect to        │                        │
        │     verify page        │                        │
        │◀─────────────────────  │                        │
        │                        │                        │
        │  3. User makes payment │                        │
        │     via mobile app     │                        │
        │                        │                        │
        │  4. JS polls API       │  5. Verify with POS    │
        │─────────────────────▶  │───────────────────────▶│
        │                        │                        │
        │                        │  6. POS Response       │
        │                        │◀───────────────────────│
        │                        │                        │
        │  7. JSON status        │                        │
        │◀─────────────────────  │                        │
        │                        │                        │
        │  8. Redirect success   │                        │
        │◀─────────────────────  │                        │
        │                        │                        │
        │  9. Update order       │                        │
        │                        │                        │
        └────────────────────────┴────────────────────────┘
```

---

## Security

### Authentication

Every API request includes:

```http
Authorization: Bearer {API_KEY}
X-Store-Secret: {API_SECRET}
```

### Store Isolation

Every transaction is validated against the configured store ID. Cross-store transactions are rejected.

### Amount Matching

Amounts must match **exactly** to 2 decimal places. No tolerance.

### Rate Limiting

Poll endpoint is rate-limited (60 requests/minute per IP+order by default).

---

## Customizing Views

After publishing views:

```bash
php artisan vendor:publish --tag=vendweave-views
```

Edit files in `resources/views/vendor/vendweave/`:

- `verify.blade.php` - Verification page
- `success.blade.php` - Success page
- `error.blade.php` - Error/cancelled page

---

## Testing

For development, use the sandbox endpoint:

```env
VENDWEAVE_API_ENDPOINT=https://sandbox.pos.vendweave.com/api
```

### Test Scenarios

1. **Successful payment** - Use matching TRX ID with correct amount
2. **Amount mismatch** - Use TRX ID with different amount
3. **Already used TRX** - Reuse the same TRX ID
4. **Timeout** - Wait for polling timeout
5. **Cancellation** - Click cancel button

---

## Changelog

### v1.0.0

- Initial release
- bKash, Nagad, Rocket, Upay support
- Polling-based verification
- Rate limiting
- Store scope isolation

---

## License

MIT License. See [LICENSE](LICENSE) for details.

---

## Support

For issues and feature requests, please open an issue on GitHub.

**VendWeave Team** - [dev@vendweave.com](mailto:dev@vendweave.com)
