# VendWeave POS Payment Verification API

## Authentication

Headers:

```
Authorization: Bearer {API_KEY}
X-Store-Secret: {API_SECRET}
```

---

## Verify Transaction Endpoint

```
GET /api/transactions/verify
```

---

## Request Parameters

| Field          | Type    | Required |
| -------------- | ------- | -------- |
| order_id       | string  | Yes      |
| trx_id         | string  | Optional |
| payment_method | string  | Yes      |
| amount         | decimal | Yes      |
| store_id       | integer | Yes      |

---

## Response Example

```json
{
  "status": "confirmed",
  "trx_id": "BKA123XYZ",
  "amount": 960.0,
  "payment_method": "bkash",
  "store_id": 5
}
```

---

## Status Values

| Status      | Description                                |
| ----------- | ------------------------------------------ |
| `pending`   | Transaction awaiting confirmation          |
| `confirmed` | Transaction verified successfully          |
| `failed`    | Transaction failed                         |
| `used`      | Transaction already used for another order |
| `expired`   | Transaction has expired                    |

---

## Validation Rules

- Amount অবশ্যই exact match করবে
- Store ID match না হলে reject
- Method mismatch হলে reject
- Used transaction পুনরায় ব্যবহার করা যাবে না
- Expired transaction invalid

---

## Error Codes

| Code                       | Description                         |
| -------------------------- | ----------------------------------- |
| `TRANSACTION_NOT_FOUND`    | Transaction ID not found in POS     |
| `AMOUNT_MISMATCH`          | Amount doesn't match exactly        |
| `METHOD_MISMATCH`          | Payment method doesn't match        |
| `STORE_MISMATCH`           | Store scope violation               |
| `TRANSACTION_ALREADY_USED` | Transaction linked to another order |
| `TRANSACTION_EXPIRED`      | Transaction is too old              |
| `INVALID_CREDENTIALS`      | Missing or invalid API credentials  |

---

## Authority Rule

> VendWeave POS হচ্ছে একমাত্র payment authority।  
> Laravel system শুধুমাত্র POS এর সিদ্ধান্ত গ্রহণ করবে।
