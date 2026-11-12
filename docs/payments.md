# Payments

## Built-in methods

Each method is switched on and off, and given instructions, in **Settings → Store settings → Payments**.

| Method | Flow |
|---|---|
| Cash on Delivery | The order is placed as `unpaid`. It becomes `paid` automatically when the status changes to **Delivered**. |
| bKash / Nagad (personal or merchant number) | The customer sends money to your number and types the **transaction ID** at checkout. The order is saved with `payment_status = pending` and the transaction ID. |
| Bank transfer | Same as bKash/Nagad: the customer enters a reference and the payment stays `pending`. |

### Verifying manual payments

No payment is trusted automatically. The admin opens the order and compares the transaction ID with the bKash/Nagad/bank statement, then clicks **Mark as paid** (Admin → Orders → view order). The action is saved in the order timeline. Until then:

- the order stays `pending`
- the order is never marked paid on the customer's word alone

## Adding an online gateway (SSLCommerz, bKash Checkout API, Nagad API, aamarPay…)

Not included. This is the recommended structure:

1. **Add the method.** Add it to `App\Services\CheckoutService::paymentMethods()` with its settings fields in `StoreSettings`.
2. **Start the payment.** After `CheckoutService::place()` creates the order as `unpaid`, redirect to the gateway with `order_number` and `total`.
3. **Verify the callback on the server. Never trust the redirect alone.** Add a route `POST /payment/{gateway}/ipn` that is excluded from CSRF. In that route:
   - call the gateway's **validation API** (for SSLCommerz, `validator/api/validationserverAPI.php` with `val_id`), or check the gateway's signature or hash
   - confirm that the amount and currency match `orders.total`, and that the `tran_id` matches `order_number`
   - make it idempotent: if the order is already `paid`, do nothing
   - only then set `payment_status = paid` and save the gateway transaction ID in `transaction_id`
4. **Handle the customer's return.** The success, fail and cancel pages only *show* a status. They read the order again from the database; they don't change it.
5. **Keep keys in `.env`** (`SSLCZ_STORE_ID`, `SSLCZ_STORE_PASSWORD`, `SSLCZ_SANDBOX=true`). Never put them in code or in the database.
