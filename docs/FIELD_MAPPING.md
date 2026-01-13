# VendWeave Field Mapping Guide

যদি তোমার Laravel project এ database field names VendWeave এর expected names এর সাথে match না করে, এই guide follow করো।

---

## 🤔 সমস্যা কি?

তোমার Orders table এ field নাম আলাদা হতে পারে:

| VendWeave Expected | তোমার Field Example             |
| ------------------ | ------------------------------- |
| `id`               | `order_id`                      |
| `total`            | `grand_total`, `amount`         |
| `payment_method`   | `pay_method`, `gateway`         |
| `status`           | `order_status`, `state`         |
| `trx_id`           | `transaction_ref`, `payment_id` |

**এটা completely স্বাভাবিক!** VendWeave তোমার schema এর সাথে adapt করতে পারে।

---

## ✅ Solution: Field Mapping

### Step 1: Config Publish করো

```bash
php artisan vendor:publish --tag=vendweave-config
```

### Step 2: config/vendweave.php এ mapping configure করো

```php
// config/vendweave.php

// তোমার Order model class
'order_model' => \App\Models\Order::class,

// Field name mapping
'order_mapping' => [
    'id' => 'order_id',            // তোমার ID column
    'amount' => 'grand_total',      // তোমার total/amount column
    'payment_method' => 'gateway',  // তোমার payment method column
    'status' => 'order_status',     // তোমার status column
    'trx_id' => 'transaction_ref',  // তোমার transaction ID column
],
```

---

## 📊 Status Value Mapping

তোমার app এ status integers বা enums হতে পারে:

| তোমার App             | VendWeave Status |
| --------------------- | ---------------- |
| `1` বা `'completed'`  | `paid`           |
| `0` বা `'processing'` | `pending`        |
| `2` বা `'cancelled'`  | `failed`         |

Configure করো:

```php
// config/vendweave.php

'status_mapping' => [
    'paid' => 'completed',     // বা 1
    'pending' => 'processing', // বা 0
    'failed' => 'cancelled',   // বা 2
],
```

---

## 🔧 OrderAdapter Service ব্যবহার

Package একটি `OrderAdapter` service দেয় যা mapping handle করে:

```php
use VendWeave\Gateway\Services\OrderAdapter;

// Get adapter instance
$adapter = app(OrderAdapter::class);

// Order খুঁজে বের করো
$order = $adapter->findOrder($orderId);

// Values পড়ো (mapping অনুযায়ী)
$amount = $adapter->getAmount($order);        // grand_total পড়বে
$method = $adapter->getPaymentMethod($order); // gateway পড়বে
$trxId = $adapter->getTrxId($order);          // transaction_ref পড়বে

// Order update করো
$adapter->markAsPaid($order, 'TRX123XYZ');    // status='completed', trx='TRX123'
$adapter->markAsFailed($order);                // status='cancelled'
```

---

## 📁 Different Table Name?

তোমার Orders table এর নাম হতে পারে:

- `sales`
- `customer_orders`
- `shop_orders`
- `transactions`

**Solution:** Model class specify করো (table নয়):

```php
// config/vendweave.php

'order_model' => \App\Models\Sale::class,
```

Package Model ব্যবহার করে, Table নয়।

---

## ❓ TRX ID Column নাই?

যদি তোমার table এ transaction ID column না থাকে:

### Option 1: Column Add করো (Recommended)

```bash
php artisan make:migration add_trx_id_to_orders
```

```php
// database/migrations/xxxx_add_trx_id_to_orders.php

public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('trx_id')->nullable()->after('status');
    });
}
```

```bash
php artisan migrate
```

### Option 2: Separate Table এ Store করো

```php
// app/Listeners/StoreTransactionId.php

use VendWeave\Gateway\Events\PaymentVerified;

class StoreTransactionId
{
    public function handle(PaymentVerified $event): void
    {
        PaymentTransaction::create([
            'order_id' => $event->orderId,
            'trx_id' => $event->getTrxId(),
            'payment_method' => $event->getPaymentMethod(),
        ]);
    }
}
```

---

## 📋 Full Example Config

```php
// config/vendweave.php

return [
    // ... other config ...

    'order_model' => \App\Models\Sale::class,

    'order_mapping' => [
        'id' => 'sale_id',
        'amount' => 'grand_total',
        'payment_method' => 'gateway',
        'status' => 'sale_status',
        'trx_id' => 'payment_reference',
    ],

    'status_mapping' => [
        'paid' => 'completed',
        'pending' => 'processing',
        'failed' => 'cancelled',
    ],
];
```

---

## 🏆 Benefits

| Benefit             | Description                                      |
| ------------------- | ------------------------------------------------ |
| ✅ No Schema Change | তোমার existing DB structure পরিবর্তন করতে হবে না |
| ✅ No Migration     | Legacy system এ কাজ করবে                         |
| ✅ Flexible         | যেকোনো naming convention সাপোর্ট করে             |
| ✅ Easy Onboarding  | কয়েক লাইন config এ setup                        |

> 💡 এটাই exactly **Stripe SDK, PayPal SDK** যেভাবে কাজ করে।

---

## 📌 Summary

```
তুমি তোমার system পরিবর্তন করবে না।
Package তোমার system এর সাথে adapt করবে।
```

**That's enterprise integration mindset.** 🚀
