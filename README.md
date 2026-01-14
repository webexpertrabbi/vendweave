# VendWeave Laravel Payment SDK

A production-grade Laravel payment SDK for VendWeave POS infrastructure, enabling secure manual payment verification for bKash, Nagad, Rocket, and Upay with real-time POS synchronization.

[![Latest Version](https://img.shields.io/packagist/v/vendweave/payment.svg)](https://packagist.org/packages/vendweave/payment)
[![License](https://img.shields.io/packagist/l/vendweave/payment.svg)](LICENSE.md)

---

## 🚀 Features

- **Store Isolation:** Secure transaction verification scoped to your specific store.
- **Real-time Polling:** Auto-polling every 2.5s for instant payment confirmation.
- **Exact Amount Verification:** Zero-tolerance amount matching prevents partial payment fraud.
- **Auto-Adaptation:** Smartly adapts to your database structure and API responses.
- **Plug & Play:** Works seamlessly with Laravel 10 & 11.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require vendweave/payment
```

---

## ⚙️ Configuration

### 1. Publish Configuration

Publish the configuration file to customize payment methods and settings:

```bash
php artisan vendor:publish --tag=vendweave-config
```

### 2. Environment Setup

Add your VendWeave credentials to your `.env` file:

```env
# API Credentials
VENDWEAVE_API_KEY=your_api_key
VENDWEAVE_API_SECRET=your_api_secret
VENDWEAVE_STORE_SLUG=your_store_slug
VENDWEAVE_API_ENDPOINT=https://vendweave.com/api

# Payment Numbers (Displayed on Verification Page)
VENDWEAVE_BKASH_NUMBER="017XXXXXXXX"
VENDWEAVE_NAGAD_NUMBER="018XXXXXXXX"
VENDWEAVE_ROCKET_NUMBER="019XXXXXXXX"
VENDWEAVE_UPAY_NUMBER="016XXXXXXXX"
```

> **Important:** Obtain your credentials from the [VendWeave Dashboard](https://vendweave.com/dashboard) under **Settings → API Credentials**. Use "General API Credentials", **not** "Manual Payment API Keys".

---

## 🛠 Usage

### 1. Initiate Payment

In your checkout controller, store the order details in the session and redirect the user to the verification route.

```php
use Illuminate\Support\Facades\Session;
use App\Models\Order;

public function checkout(Request $request)
{
    // 1. Create your local order
    $order = Order::create([
        'total' => 500.00,
        'status' => 'pending',
        'payment_method' => 'bkash', // bkash, nagad, rocket, or upay
    ]);

    // 2. Store required data in Session for the SDK
    Session::put("vendweave_order_{$order->id}", [
        'amount' => $order->total,
        'payment_method' => $order->payment_method,
    ]);

    // 3. Redirect to the standardized verification page
    return redirect()->route('vendweave.verify', ['order' => $order->id]);
}
```

### 2. Handle Payment Events

Register listeners in your `EventServiceProvider` to handle successful or failed payments.

**`app/Providers/EventServiceProvider.php`**:

```php
use VendWeave\Gateway\Events\PaymentVerified;
use VendWeave\Gateway\Events\PaymentFailed;
use App\Listeners\MarkOrderAsPaid;
use App\Listeners\HandleFailedPayment;

protected $listen = [
    PaymentVerified::class => [MarkOrderAsPaid::class],
    PaymentFailed::class => [HandleFailedPayment::class],
];
```

**Example Listener (`MarkOrderAsPaid.php`):**

```php
public function handle(PaymentVerified $event)
{
    $order = $event->order;
    $result = $event->verificationResult;

    // Update your order status
    $order->update([
        'status' => 'paid',
        'trx_id' => $result->getTransactionId(),
    ]);
}
```

---

## 🎨 Customizable Instructions

You can customize the payment instructions (e.g., "Send Money" vs "Payment") and the phone numbers in `config/vendweave.php`. This is useful if you want to change the text language or payment type.

```php
// config/vendweave.php
'payment_methods' => [
    'bkash' => [
        'number' => env('VENDWEAVE_BKASH_NUMBER'),
        'type' => 'personal', // or 'merchant'
        'instruction' => 'bKash App -> Send Money option.',
    ],
    // ...
],
```

---

## 🔧 Troubleshooting

- **SSL Errors (Localhost):** If you face SSL certificate issues locally, set `VENDWEAVE_VERIFY_SSL=false` in `.env`. **Always set to true in production.**
- **Method Mismatch:** Ensure your Order model has a `payment_method` attribute or accessor that returns one of: `bkash`, `nagad`, `rocket`, `upay`.
- **401 Unauthorized:** Verify you are using the correct Store Slug and API Keys from the dashboard.

---

## 📜 License

MIT License. See [LICENSE](LICENSE) for details.
