# Order Management

This article covers how Mollie Payments for Magento 2 handles order statuses, invoicing, payment capture, refunds, and recovery of pending orders.

## Order Statuses

The extension assigns two configurable statuses to every Mollie order: one when the order is created and the customer is redirected to the payment page, and one after a successful payment is confirmed.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Status Pending** to the status to use while awaiting payment (default: `pending_payment`)
3. Set **Status Processing** to the status to use after a successful payment (default: `processing`)
4. Click **Save Config** and flush the cache

Bank transfer orders remain in the pending status until Mollie confirms the transfer has been received, which can take several business days. Configure a dedicated status for these orders (for example, `pending_banktransfer`) to distinguish them from other unprocessed orders in the order grid — see [Best Practices](BEST_PRACTICES.md) for details.

## Invoice Creation

The extension creates a Magento invoice automatically when it receives confirmation of a successful payment. Disable this only if invoices are managed by an external system such as an ERP.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Create invoice on successful payment** to **Yes** (default) or **No**
3. Click **Save Config** and flush the cache

### Invoice Email

When an invoice is created, the extension can send the invoice email to the customer automatically. Klarna orders have a separate toggle so you can suppress Magento's invoice email when Klarna sends its own invoicing communication.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Send Invoice Email** to **Yes** or **No**
3. Set **Send Invoice Email For Klarna Orders** to **Yes** or **No**
4. Click **Save Config** and flush the cache

## Capture Modes

Some payment methods support a choice between automatic and manual capture. With automatic capture, the payment is settled the moment the customer completes it. With manual capture, the payment is authorised but no funds move until you trigger the capture from Magento Admin — useful when you need to review, adjust, or cancel orders before charging the customer.

Capture settings are configured per payment method. Methods with a fixed capture mode (such as Riverty, which always uses manual capture) do not show the capture settings.

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand the payment method you want to configure (for example, **Credit Card**)
3. Set **Capture method** to **Autocapture** or **Manual capture**
4. Click **Save Config** and flush the cache

### Manual Capture

When **Manual capture** is selected, you must also configure when the capture is triggered.

1. Set **When to capture?** to **On invoice** or **On shipment**:
   - **On invoice** — the capture is sent to Mollie when you create an invoice for the order in Magento Admin
   - **On shipment** — the capture is sent when you create a shipment; the extension auto-generates the invoice for the shipped items first, then captures them
2. Optionally set a **Capture expiration window** to limit how long an authorisation can remain open

For Klarna and Billie, the default is **On shipment**, which matches the buy-now-pay-later flow where the customer is only charged once the goods are dispatched. If you do not create a shipment in Magento, the capture is never triggered.

**Important:** An authorisation that is not captured before the expiration window closes is released automatically. Once released, the order cannot be captured and must be cancelled.

Cancelling an order that has not been fully captured releases the remaining authorisation at Mollie automatically, so the reserved amount is returned to the customer without waiting for the expiration window. If the release request fails, the order is still cancelled in Magento and a warning is shown in Magento Admin; Mollie then releases the uncaptured amount itself when the authorisation expires.

### Automatic Capture Delay

For methods using **Autocapture**, you can insert a delay between authorisation and capture. This gives you a window to review or cancel orders before the customer is charged.

1. Set **Capture method** to **Autocapture**
2. Enter a value in **Capture delay** and select **Hours** or **Days** as the unit
3. Click **Save Config** and flush the cache

## Refunds

Creating a credit memo in Magento Admin for an order paid via Mollie sends a refund request to the Mollie API automatically. The customer receives the refund on a timeline determined by their payment method.

1. Go to **Sales → Orders** and open the order
2. Open the **Invoices** tab, click the invoice, then click **Credit Memo**
3. Adjust quantities or amounts as needed
4. Click **Refund** — do not use **Refund Offline**, as that skips the API call to Mollie

The Mollie refund ID is stored against the credit memo for reference.

If a refund is initiated directly in the Mollie Dashboard — for example, by a support agent — the extension detects it on the next webhook call and creates the corresponding credit memo in Magento automatically, without triggering a second API call.

**Note on gift card and voucher orders:** If the customer paid part of the order with a voucher or gift card, that portion may not be refundable online. The amount paid through the remaining payment method can always be refunded; the credit memo page shows this amount in a warning. If a refund above that amount fails, settle the remaining part directly with the voucher or gift card issuer.

## Failed Payment Handling

### Redirect After a Failed Payment

When a payment is cancelled or fails (for example, a declined card or insufficient funds), configure where the customer is sent.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Redirect user when redirect fails** to one of:
   - **Redirect to cart** (default) — returns the customer to their cart
   - **Redirect to checkout (shipping)** — returns to the shipping step
   - **Redirect to checkout (payment)** — returns to the payment selection step
3. Click **Save Config** and flush the cache

Error messages such as "Payment was cancelled" may not display on all checkout implementations. Test error handling after changing this setting.

### Cancel Order on Checkout Return

When a customer uses the browser back button while a payment is still pending, the extension can automatically cancel the order and restore the cart. This releases reserved stock immediately.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Cancel order on checkout return** to **Yes**
3. Click **Save Config** and flush the cache

**Important:** Enable this only when frequent stock shortages make it necessary. If a customer briefly navigates away to check order details and then returns to complete payment, this setting cancels their order.

### Cancel Order When Connection Fails

If the Mollie transaction cannot be created due to a connection failure or a data validation error, the extension can cancel the just-created Magento order automatically rather than leaving it in an unresolvable pending state.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Cancel order when connection fails** to **Yes**
3. Click **Save Config** and flush the cache

## Pending Orders Recovery

The pending orders cron job periodically checks orders that are still in a pending state in Magento and queries the Mollie API for their current payment status. This recovers orders where the payment succeeded but the webhook did not reach the store — for example, due to a temporary network failure or a misconfigured firewall rule.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Enable Pending Orders Cron Job** to **Yes**
3. Set **Pending Orders Cron Job Batch Size** — the default of `25` is appropriate for most stores; reduce it if cron resources are constrained
4. Click **Save Config** and flush the cache

The cron job requires Magento's cron scheduler to be running. Verify the scheduler is active before relying on this as a recovery mechanism. Webhooks are the primary update path; the cron job is a fallback only.

## Next Steps

- [Configuration](CONFIGURATION.md) — All general settings
- [Payment Methods](PAYMENT_METHODS.md) — Per-method settings including capture configuration
- [Klarna](KLARNA.md) — Klarna-specific capture and invoicing behaviour
- [Best Practices](BEST_PRACTICES.md) — Recommended production settings for order management
- [Troubleshooting](TROUBLESHOOTING.md) — Common order status issues
