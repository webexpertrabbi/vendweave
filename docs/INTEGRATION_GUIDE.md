# VendWeave Laravel Integration Guide

## Laravel এ কি থাকতে হবে?

orders table:

```
- id
- total
- payment_method
- status
- trx_id
```

---

## Package Install

```bash
composer require vendweave/gateway
php artisan vendor:publish --tag=vendweave-config
```

---

## Environment Setup

```env
VENDWEAVE_API_KEY=your_api_key
VENDWEAVE_API_SECRET=your_api_secret
VENDWEAVE_STORE_ID=your_store_id
VENDWEAVE_API_ENDPOINT=https://pos.vendweave.com/api
```

---

## Checkout Controller Example

```php
public function checkout(Request $request)
{
    $order = Order::create([
        'total' => $request->total,
        'payment_method' => $request->payment_method,
        'status' => 'pending'
    ]);

    // Store order data in session
    Session::put("vendweave_order_{$order->id}", [
        'amount' => $order->total,
        'payment_method' => $order->payment_method,
    ]);

    return redirect()->route('vendweave.verify', ['order' => $order->id]);
}
```

---

## Verify Page

User যাবে:

```
/vendweave/verify/{order}
```

এই পেজে:

- Amount দেখাবে
- Method দেখাবে
- Trx input থাকবে
- Auto polling চলবে

---

## Payment Confirm হলে

```php
use VendWeave\Gateway\Facades\VendWeave;

$result = VendWeave::verify($orderId, $amount, $paymentMethod);

if ($result->isConfirmed()) {
    $order->update([
        'status' => 'paid',
        'trx_id' => $result->getTrxId()
    ]);
}
```

---

## Using Events (Recommended)

Listen to payment events in your `EventServiceProvider`:

```php
use VendWeave\Gateway\Events\PaymentVerified;
use VendWeave\Gateway\Events\PaymentFailed;

protected $listen = [
    PaymentVerified::class => [
        UpdateOrderStatus::class,
    ],
    PaymentFailed::class => [
        HandleFailedPayment::class,
    ],
];
```

---

## Payment Lifecycle

```
Checkout
→ Verify Page
→ POS Confirm
→ Order Paid
→ Success Page
```

---

## Important Rules

> ⚠️ Laravel কখনো payment decide করে না।  
> ⚠️ VendWeave POS সবসময় authority।

---

## Helper Class

```php
use VendWeave\Gateway\VendWeaveHelper;

// Prepare payment and get redirect URL
$url = VendWeaveHelper::preparePayment($orderId, $amount, 'bkash');
return redirect($url);

// Get payment methods
$methods = VendWeaveHelper::getPaymentMethods();

// Validate method
if (VendWeaveHelper::isValidPaymentMethod('nagad')) {
    // ...
}
```

---

## Routes Available

| Route                         | Name                | Description       |
| ----------------------------- | ------------------- | ----------------- |
| `/vendweave/verify/{order}`   | `vendweave.verify`  | Verification page |
| `/vendweave/success/{order}`  | `vendweave.success` | Success page      |
| `/vendweave/failed/{order}`   | `vendweave.failed`  | Failure page      |
| `/api/vendweave/poll/{order}` | `vendweave.poll`    | Polling endpoint  |

---

## Custom Success/Failure Routes

```php
// config/vendweave.php
'callbacks' => [
    'success_route' => 'shop.order.complete',
    'failed_route' => 'shop.order.failed',
],
```

---

## Testing

Use sandbox endpoint for development:

```env
VENDWEAVE_API_ENDPOINT=https://sandbox.pos.vendweave.com/api
```
