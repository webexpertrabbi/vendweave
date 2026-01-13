# VendWeave Laravel Payment Gateway

VendWeave একটি production-grade Laravel payment gateway package, যা VendWeave POS infrastructure ব্যবহার করে নিরাপদভাবে payment verification সম্পন্ন করে।

এই প্যাকেজটি **bKash, Nagad, Rocket এবং Upay** সমর্থন করে।

---

## 🚀 Features

| Feature                  | Description                                |
| ------------------------ | ------------------------------------------ |
| 🔐 Secure Authentication | API Key + Secret based authentication      |
| 🏪 Store Isolation       | Store-scoped transaction verification      |
| 💰 Exact Amount Match    | Zero tolerance - amount must match exactly |
| ⚡ Real-time Polling     | Auto-polling every 2.5 seconds             |
| 🎨 Fintech UI            | Dark theme, mobile-first verification page |
| 🚦 Rate Limiting         | Built-in protection against abuse          |
| 🧩 Laravel Native        | Works with Laravel 10 & 11                 |
| 🧾 POS Authority         | POS is single source of truth              |

---

## 💳 Supported Payment Methods

| Method | Status       |
| ------ | ------------ |
| bKash  | ✅ Supported |
| Nagad  | ✅ Supported |
| Rocket | ✅ Supported |
| Upay   | ✅ Supported |

---

## ⚡ Quick Start (5 Minutes)

### Step 1: Install Package

```bash
composer require vendweave/gateway
```

### Step 2: Publish Config

```bash
php artisan vendor:publish --tag=vendweave-config
```

### Step 3: Add Environment Variables

```env
VENDWEAVE_API_KEY=your_api_key
VENDWEAVE_API_SECRET=your_api_secret
VENDWEAVE_STORE_SLUG=your_store_slug
VENDWEAVE_API_ENDPOINT=https://vendweave.com/api
```

### Step 4: Redirect to Verify Page

```php
use Illuminate\Support\Facades\Session;

// After creating order, store data in session
Session::put("vendweave_order_{$order->id}", [
    'amount' => $order->total,
    'payment_method' => 'bkash',
]);

// Redirect to verify page
return redirect()->route('vendweave.verify', ['order' => $order->id]);
```

**Done!** User will see the verification page and payment will be auto-verified.

---

## 🏗 Architecture

```
┌─────────────────┐     ┌─────────────────────┐     ┌─────────────────┐
│   Laravel App   │ ──► │  VendWeave Package  │ ──► │  VendWeave POS  │
│   (Your Shop)   │     │   (This Package)    │     │   (Authority)   │
└─────────────────┘     └─────────────────────┘     └─────────────────┘
```

> ⚠️ **Important**: Laravel কখনো নিজে payment success সিদ্ধান্ত নেয় না।  
> VendWeave POS সবসময় authority।

---

## 🔁 Payment Flow

```
1. User Checkout
      ↓
2. Select Payment Method (bKash/Nagad/Rocket/Upay)
      ↓
3. Redirect to Verify Page (/vendweave/verify/{order})
      ↓
4. User Pays via Mobile App
      ↓
5. Package Polls POS API (every 2.5s)
      ↓
6. POS Confirms → Order Marked Paid
      ↓
7. Redirect to Success Page
```

---

## 🛣️ Routes

| Route                         | Name                | Description               |
| ----------------------------- | ------------------- | ------------------------- |
| `/vendweave/verify/{order}`   | `vendweave.verify`  | Payment verification page |
| `/vendweave/success/{order}`  | `vendweave.success` | Payment success page      |
| `/vendweave/failed/{order}`   | `vendweave.failed`  | Payment failed page       |
| `/api/vendweave/poll/{order}` | `vendweave.poll`    | AJAX polling endpoint     |

---

## 🚨 Error Codes

| Error Code                 | Description                            | Action                         |
| -------------------------- | -------------------------------------- | ------------------------------ |
| `TRANSACTION_NOT_FOUND`    | No matching transaction found          | User needs to complete payment |
| `AMOUNT_MISMATCH`          | Amount doesn't match                   | Check order total              |
| `METHOD_MISMATCH`          | Payment method doesn't match           | Verify method selected         |
| `STORE_MISMATCH`           | Transaction belongs to different store | Security violation             |
| `TRANSACTION_ALREADY_USED` | TRX ID already used                    | Possible fraud                 |
| `TRANSACTION_EXPIRED`      | Transaction too old                    | Timeout - retry payment        |
| `INVALID_CREDENTIALS`      | API key/secret invalid                 | Check .env configuration       |

---

## 🔐 Security Features

- ✅ **API Authentication**: Every request requires API Key + Secret
- ✅ **Store Isolation**: Transactions validated against store_slug
- ✅ **Exact Amount**: No tolerance - prevents partial payment fraud
- ✅ **No Reuse**: Transaction IDs cannot be used twice
- ✅ **Rate Limiting**: 60 requests/minute per order
- ✅ **Logging**: All API calls logged (configurable)

---

## 📚 Documentation

| Document                                       | Description                          |
| ---------------------------------------------- | ------------------------------------ |
| [Integration Guide](docs/INTEGRATION_GUIDE.md) | Step-by-step Laravel integration     |
| [Field Mapping](docs/FIELD_MAPPING.md)         | Map your DB fields to package fields |
| [API Contract](docs/API_CONTRACT.md)           | POS API specification                |
| [Website Copy](docs/WEBSITE_COPY.md)           | Marketing copy for your website      |

---

## 🧩 Facade Usage

```php
use VendWeave\Gateway\Facades\VendWeave;

// Verify a transaction
$result = VendWeave::verify($orderId, $amount, 'bkash');

if ($result->isConfirmed()) {
    // Payment successful!
    $trxId = $result->getTrxId();
}

// Check payment methods
$methods = VendWeave::getPaymentMethods();

// Validate method
VendWeave::isValidPaymentMethod('nagad'); // true
```

---

## 🎯 Events

Listen to payment events in `EventServiceProvider`:

```php
use VendWeave\Gateway\Events\PaymentVerified;
use VendWeave\Gateway\Events\PaymentFailed;

protected $listen = [
    PaymentVerified::class => [
        \App\Listeners\MarkOrderAsPaid::class,
    ],
    PaymentFailed::class => [
        \App\Listeners\HandleFailedPayment::class,
    ],
];
```

---

## ⚙️ Configuration Options

```php
// config/vendweave.php

'polling' => [
    'interval_ms' => 2500,      // Poll every 2.5 seconds
    'max_attempts' => 120,       // Max 120 attempts (5 minutes)
    'timeout_seconds' => 300,    // Overall timeout
],

'rate_limit' => [
    'max_attempts' => 60,        // 60 requests per minute
    'decay_minutes' => 1,
],
```

---

## ✅ Production Status

| Item            | Status               |
| --------------- | -------------------- |
| Version         | **v1.0.0**           |
| Stability       | **Production Ready** |
| Laravel Support | 10.x, 11.x           |
| PHP Support     | 8.1+                 |

---

## 📜 License

MIT License - See [LICENSE](LICENSE) file.

---

## 🆘 Support

For issues and feature requests, please open an issue on GitHub.

---

**VendWeave — Powering Trusted Digital Payments 🚀**
