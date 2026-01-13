# VendWeave Field Mapping Guide

## সমস্যা

তোমার Laravel project এ field নাম VendWeave এর expected field এর সাথে ১০০% match করবে না।

| VendWeave Expected | তোমার Actual Example |
| ------------------ | -------------------- |
| total              | grand_total          |
| payment_method     | pay_method           |
| status             | order_status         |
| trx_id             | transaction_ref      |

এটা completely normal।

---

## Solution: Configurable Field Mapping

VendWeave package আসে built-in field mapping support সহ।

### Step 1: config/vendweave.php publish করো

```bash
php artisan vendor:publish --tag=vendweave-config
```

### Step 2: Field Mapping Configure করো

```php
// config/vendweave.php

'order_model' => App\Models\Sale::class,  // তোমার Order model

'order_mapping' => [
    'id' => 'order_id',              // তোমার ID column
    'amount' => 'grand_total',        // তোমার Amount column
    'payment_method' => 'pay_method', // তোমার Payment method column
    'status' => 'order_status',       // তোমার Status column
    'trx_id' => 'transaction_ref',    // তোমার TRX ID column
],
```

---

## Status Mapping

তোমার app এ status integer হতে পারে:

| Your App | VendWeave |
| -------- | --------- |
| 1        | paid      |
| 2        | pending   |
| 3        | failed    |

Configure করো:

```php
'status_mapping' => [
    'paid' => 1,      // confirmed হলে 1 set হবে
    'pending' => 2,   // pending হলে 2
    'failed' => 3,    // failed হলে 3
],
```

---

## OrderAdapter Service ব্যবহার

```php
use VendWeave\Gateway\Services\OrderAdapter;

$adapter = app(OrderAdapter::class);

// Order খুঁজে বের করো
$order = $adapter->findOrder($orderId);

// Amount পড়ো (mapping অনুযায়ী)
$amount = $adapter->getAmount($order);

// Payment method পড়ো
$method = $adapter->getPaymentMethod($order);

// Order paid করো
$adapter->markAsPaid($order, 'TRX123XYZ');

// Order failed করো
$adapter->markAsFailed($order);
```

---

## Table Name Different হলে?

Order table নাম হতে পারে:

- `sales`
- `customer_orders`
- `shop_orders`

Solution:

```php
'order_model' => App\Models\Sale::class,
```

Package Model ব্যবহার করে, Table নয়।

---

## TRX ID Column নাই?

যদি তোমার table এ trx_id column না থাকে:

**Option 1:** Nullable column add করো

```bash
php artisan make:migration add_trx_id_to_orders
```

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('trx_id')->nullable()->after('status');
});
```

**Option 2:** Event listener দিয়ে handle করো

```php
// Listen to PaymentVerified event
// Store trx_id in separate table
```

---

## Enterprise Benefits

✅ No forced schema change  
✅ No breaking legacy system  
✅ No DB migration needed  
✅ No coupling  
✅ Easy onboarding

এটাই exactly Stripe SDK, PayPal SDK যেভাবে কাজ করে।

---

## Summary

তুমি তোমার system পরিবর্তন করবে না।  
Package তোমার system এর সাথে adapt করবে।

**That's enterprise integration mindset.**
