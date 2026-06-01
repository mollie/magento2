# Shipment Tracking and Analytics Cookies

Mollie Payments for Magento 2 provides two tracking features: shipment-based capture, which notifies Mollie when you dispatch an order and triggers payment settlement for buy-now-pay-later methods, and analytics cookie forwarding, which carries first-party tracking cookies through the Mollie redirect so your analytics attribution stays intact.

## Prerequisites

- Mollie Payments for Magento 2 is installed and an API key is configured — see [API Keys](API_KEYS.md)
- For shipment-based capture: the relevant payment method must be configured with **Manual capture** and **On shipment** — see [Order Management](ORDER_MANAGEMENT.md)

---

## Shipment-Based Capture

### Why this matters for buy-now-pay-later

Buy-now-pay-later methods such as Klarna and Billie authorise the payment when the customer places the order but do not settle it immediately. Mollie requires a shipment notification before it releases funds to you. Creating a shipment in Magento Admin is the trigger for that notification. If you never create a shipment in Magento, the authorisation expires and the order cannot be captured.

### Which payment methods use shipment-based capture

| Method | Default capture trigger | Can be changed |
|---|---|---|
| Klarna | On shipment | Yes |
| Billie | On shipment | Yes |
| Credit Card | On invoice | Yes |
| Mobile Pay | On invoice | Yes |
| Vipps | On invoice | Yes |
| Riverty | On shipment (fixed) | No |

For Klarna and Billie the default is **On shipment**, which matches the buy-now-pay-later flow. Riverty always uses manual capture on shipment and does not expose the setting in Admin.

### Configure when capture is triggered

Each method that supports manual capture has its own **When to capture?** setting.

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand the payment method you want to configure (for example, **Klarna**)
3. Set **Capture method** to **Manual capture**
4. Set **When to capture?** to **On shipment** or **On invoice**
5. Click **Save Config** and flush the cache

**On shipment** — the extension creates an invoice automatically for the shipped items and sends the capture request to Mollie when you save the shipment.

**On invoice** — the capture is sent to Mollie when you create an invoice manually from the order in Magento Admin.

### Create a shipment and trigger capture

1. Go to **Sales → Orders** and open the order
2. Click **Ship**
3. Fill in the shipment details, including any tracking information from your carrier
4. Click **Submit Shipment**

The extension captures the shipped items on Mollie in the same request. The Magento shipment record stores the Mollie shipment ID for reference.

**Important:** If a partial shipment is created, only the value of the shipped items is captured. A second capture is sent when you create a subsequent shipment for the remaining items.

**Important:** An authorisation has a limited validity window. If the window closes before a shipment is created in Magento, Mollie releases the authorisation automatically and the order cannot be captured. Set a **Capture expiration window** on the payment method configuration if you want to bound this period explicitly — see [Order Management](ORDER_MANAGEMENT.md).

### Verify that capture was sent

1. Go to **Sales → Orders** and open the order
2. Open the **Invoices** tab — an invoice for the shipped items should be present with status **Paid**
3. Open the [Mollie Dashboard](https://my.mollie.com/dashboard) and locate the order — the payment should show as captured

If the invoice is in **Pending** state after the shipment was created, the capture request failed. Check `var/log/mollie.log` for the error detail.

---

## Analytics Cookie Forwarding

### What this does

When a customer places an order, the extension reads configured browser cookies (such as the Google Analytics `_ga` cookie) and carries their values through the Mollie payment redirect as query parameters. After the payment completes and the customer returns to your success page, the same parameters are appended to the success page URL so that client-side JavaScript can read them and attribute the conversion correctly.

Cookie values are stored against the cart at order submission time and are forwarded raw — the extension does not interpret or transform them.

### Configure tracking cookies

The **Tracking cookies** table is pre-populated with a row for the `_ga` cookie, aliased as `clientId`. Add, remove, or replace rows to match the cookies present in your checkout.

1. Go to **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Locate the **Tracking cookies** table
3. For each cookie you want to forward, click **Add cookie** and fill in:
   - **Cookie name** — the exact name of the browser cookie, for example `_ga` or `_fbp`
   - **Alias / query param** — the query parameter name used in the redirect URL and success page URL, for example `clientId` or `fbp`. Use only alphanumeric characters and underscores.
4. Remove any rows you do not need using the delete control on each row
5. Click **Save Config** and flush the cache

The alias must be unique within the table. If two rows share the same alias, only the first is used.

### How cookies flow through the redirect

When the customer submits the checkout:

1. The extension reads the value of each configured cookie from the browser request.
2. The collected values are stored in the `mollie_payment_tracking` database table, keyed by cart ID.
3. Each alias and its raw cookie value are appended as query parameters to the Mollie redirect URL, for example: `https://checkout.mollie.com/pay/...?clientId=GA1.2.123456789.1234567890`
4. After the customer completes payment and returns to your store, the parameters that were on the return URL are appended to the success page URL so that analytics scripts on that page can read them.

**Tip:** The default configuration forwards the Google Analytics client ID under the alias `clientId`. If you use Google Analytics 4, confirm that the cookie name in your store matches `_ga` before relying on the default row.

### Verify cookie forwarding is working

1. Enable **Debug requests** under **Stores → Configuration → Mollie → General → Debug & Logging**
2. Place a test order
3. Open `var/log/mollie.log` and search for the transaction request — the `redirectUrl` field should include the alias and its value as query parameters

Alternatively, open browser developer tools during a test checkout and inspect the URL you are redirected to when clicking **Pay**. If the configured cookies were present in the browser at checkout, their values appear as query parameters on that URL.

---

## Next Steps

- [Order Management](ORDER_MANAGEMENT.md) — Capture modes, expiration windows, and invoicing
- [Klarna](KLARNA.md) — Klarna-specific capture behaviour and invoicing
- [Configuration](CONFIGURATION.md) — All general settings
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues with capture and order settlement
