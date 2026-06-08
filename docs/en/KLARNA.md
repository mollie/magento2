# Klarna Payments

This page covers how to enable Klarna in Mollie Payments for Magento 2, configure capture behaviour, manage orders, and handle refunds.

## Prerequisites

- Mollie Payments for Magento 2 is installed and an API key is configured — see [API Keys](API_KEYS.md)
- Klarna is activated on your Mollie account. Log in to the [Mollie Dashboard](https://www.mollie.com/dashboard) and go to **Payment methods** to request activation if needed.

## About Klarna

Klarna is a buy-now-pay-later payment method that lets customers choose how to pay at checkout: immediately, after delivery, or in instalments. Mollie presents the Klarna option that is available for the customer's country, billing address, and cart total. You do not need to integrate each Klarna product separately — Mollie determines which product to offer based on eligibility.

Klarna requires order lines (individual line items with names, quantities, and unit prices) to be sent with each transaction. The extension builds these automatically from the Magento order.

## Enable Klarna

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand **Klarna**
3. Set **Enabled** to **Yes**
4. Optionally update the **Title** field — this is the label shown to customers at checkout (default: `Pay with Klarna.`)
5. Click **Save Config** and flush the cache — Klarna does not appear at checkout until the cache is cleared

## Capture Mode

Klarna payments are authorised at the time the customer completes the checkout. Funds are not collected until you capture the authorisation. The extension supports two capture modes.

### Manual Capture (default)

The default capture mode for Klarna is **Manual capture** with **When to capture?** set to **On shipment**. This matches the standard Klarna flow: the customer is charged only when their goods are dispatched.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Klarna**
2. Confirm **Capture method** is set to **Manual capture**
3. Set **When to capture?** to:
   - **On shipment** — the extension captures the authorisation and creates an invoice automatically when you create a shipment in Magento Admin. This is the recommended option for Klarna.
   - **On invoice** — the capture is sent to Mollie when you manually create an invoice for the order
4. Click **Save Config** and flush the cache

**Important:** If you choose **On shipment** but never create a shipment record in Magento Admin, the authorisation is never captured. Set a **Capture expiration window** (see below) to ensure the authorisation does not expire silently.

### Capture Expiration Window

The capture expiration window limits how long an authorisation can stay open. When the window closes, Mollie releases the authorisation automatically and the order can no longer be captured.

The expiration window field appears in the Klarna settings when **Manual capture** is selected. It is rendered as a custom field that also shows the Mollie-enforced maximum for this method — do not set a value that exceeds the maximum shown.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Klarna**
2. Enter a value in **Capture expiration window**
3. Click **Save Config** and flush the cache

**Important:** Once an authorisation expires, the order cannot be recovered. Cancel the Magento order and ask the customer to place a new order.

### Automatic Capture with a Delay

If you want Klarna payments to be captured automatically but with a short review window, use **Autocapture** with a delay.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Klarna**
2. Set **Capture method** to **Autocapture**
3. Enter a value in **Capture delay** and select **Hours** or **Days** as the unit
4. Click **Save Config** and flush the cache

## Checkout Flow

Klarna uses a redirect-based checkout. After the customer selects Klarna at the payment step and places the order, they are redirected to a Klarna-hosted page where they authenticate and confirm the payment. After completing the Klarna flow, they are returned to your store's success page.

The extension sends the full order line breakdown (products, shipping, discounts, taxes) to Klarna when creating the transaction. Klarna uses these lines to display an itemised summary to the customer and to validate the order total. Ensure that your tax and discount configuration produces accurate line totals, as mismatches will cause the transaction to fail.

## Capturing a Klarna Order (On Shipment)

When **When to capture?** is set to **On shipment**, create a shipment in Magento Admin to trigger the capture.

1. Go to **Sales → Orders** and open the Klarna order
2. Click **Ship**
3. Fill in the shipment details (carrier, tracking number, items to ship)
4. Click **Submit Shipment**

The extension sends the capture request to Mollie automatically when the shipment is saved. An invoice is created for the shipped items at the same time. Partial shipments are supported — you can ship and capture items in multiple batches until the full order is captured.

## Capturing a Klarna Order (On Invoice)

When **When to capture?** is set to **On invoice**, create an invoice manually to trigger the capture.

1. Go to **Sales → Orders** and open the Klarna order
2. Click **Invoice**
3. Adjust quantities if you are creating a partial invoice
4. Click **Submit Invoice**

The extension sends the capture request to Mollie when the invoice is submitted.

## Refunds

Klarna supports both full and partial refunds. Refunds are initiated from the credit memo screen in Magento Admin and sent to Mollie automatically.

1. Go to **Sales → Orders** and open the Klarna order
2. Open the **Invoices** tab, click the invoice, then click **Credit Memo**
3. Adjust quantities or amounts as needed
4. Click **Refund** — do not use **Refund Offline**, as that skips the Mollie API call

The refund is processed by Klarna on their standard timeline. Klarna refunds are valid for up to three years from the original transaction date.

## Invoice Email

When a Klarna order is captured, Klarna sends its own payment confirmation and invoice to the customer directly. To avoid sending a duplicate invoice email from Magento, the extension provides a separate toggle for Klarna orders.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Confirm **Send Invoice Email** is set to **Yes** (or **No** if you never want invoice emails)
3. Set **Send Invoice Email For Klarna Orders** to **Yes** to also send the Magento invoice email for Klarna orders, or **No** to suppress it (recommended when Klarna's own email is sufficient)
4. Click **Save Config** and flush the cache

## Country and Currency Restrictions

Klarna is available in the following countries: Austria, Belgium, Czech Republic, Denmark, Finland, France, Germany, Greece, Hungary, Ireland, Italy, Netherlands, Norway, Poland, Portugal, Romania, Slovakia, Spain, Sweden, Switzerland, and the United Kingdom.

Accepted currencies depend on the customer's billing country:

| Country | Currency |
|---|---|
| Austria, Belgium, Finland, France, Germany, Greece, Hungary, Ireland, Italy, Netherlands, Portugal, Romania, Slovakia, Spain | EUR |
| Czech Republic | CZK |
| Denmark | DKK |
| Norway | NOK |
| Poland | PLN |
| Sweden | SEK |
| Switzerland | CHF |
| United Kingdom | GBP |

To restrict Klarna to a subset of supported countries in your store:

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Klarna**
2. Set **Payment from Applicable Countries** to **Specific Countries**
3. Select the countries in **Payment from Specific Countries**
4. Click **Save Config** and flush the cache

Alternatively, enable the Methods API under **Stores → Configuration → Mollie → Developer Settings → Advanced**, which queries Mollie in real time and filters out any method that is not available for the customer's country and cart total, including Klarna.

## Order Total Limits

Klarna has per-country minimum and maximum transaction amounts enforced by Mollie. You can set additional limits in Magento to prevent Klarna from appearing for orders outside a chosen range.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Klarna**
2. Enter a value in **Minimum Order Total** and/or **Maximum Order Total**
3. Click **Save Config** and flush the cache

## Payment Surcharge

A surcharge can be added to Klarna orders to recover processing costs. For configuration details, see [Payment Fee](PAYMENT_FEE.md).

## Next Steps

- [Order Management](ORDER_MANAGEMENT.md) — Capture modes, invoicing, and refund behaviour across all methods
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to payment methods
- [Configuration](CONFIGURATION.md) — General settings including the Methods API
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues with order status and capture failures
