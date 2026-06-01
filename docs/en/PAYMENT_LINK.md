# Payment Link / Admin Payment

Payment Link is an admin-only payment method. It does not appear in the storefront checkout. Instead, it lets a Magento Admin user create an order on behalf of a customer and generate a Mollie-hosted payment link that can be sent to the customer through any channel (email, SMS, etc.).

## Prerequisites

- Mollie Payments for Magento 2 is installed and enabled — see [Installation](INSTALLATION.md)
- A valid live API key is configured — see [API Keys](API_KEYS.md)

## Configuration

Go to **Stores → Configuration → Mollie → Payment Methods → Payment Link / Admin Payment**.

| Field | Description |
|---|---|
| **Enabled** | Set to **Yes** to make the method available in Magento Admin order creation |
| **Add Link to Payment Details** | When **Yes**, the payment link is stored in the order's payment information and displayed in the payment details block on the order view page |
| **Payment Message / Link** | The message template added to the payment info block. Use `%link%` where you want the URL to appear. Visible only when **Add Link to Payment Details** is **Yes** |
| **Allow orders to be marked as paid manually** | When **Yes**, a **Mark as paid** button appears on the order view page — see [Marking an Order as Paid](#marking-an-order-as-paid) |
| **Status After Creation** | The order status assigned immediately after the admin creates the order. Use a custom status (for example, `waiting_for_payment`) to distinguish these orders from standard pending orders in the order grid |
| **Capture method** | Capture behaviour; see [Order Management](ORDER_MANAGEMENT.md) for details |

### Custom Payment Link URL

By default, the generated link points to `yourdomain.com/mollie/checkout/paymentlink?order=...`. For headless storefronts or custom checkout flows, you can override the base URL.

1. Go to **Stores → Configuration → Mollie → Developer Settings → PWA Storefront Integration**
2. Set **Use custom payment link url?** to **Yes**
3. Enter the custom URL in **Custom payment link url**. Use `{{order}}` as a placeholder for the encrypted order identifier:

   ```
   https://my-headless-frontend.com/checkout/payment-link/{{order}}
   ```

   If `{{order}}` is absent, the encrypted identifier is appended to the end of the URL.

4. Click **Save Config** and flush the cache.

When the customer opens a custom URL, your frontend is responsible for resolving the payment link — see [Payment Link Redirect](HEADLESS.md#payment-link-redirect) in the headless integration documentation.

## Creating an Admin Payment

### 1. Open the new order form

Go to **Sales → Orders** and click **Create New Order**.

Select an existing customer or create a guest order.

### 2. Build the order

Add products, a billing address, and a shipping address as you would for any admin order. A shipping method is required unless all items are virtual.

### 3. Select Payment Link / Admin Payment

In the **Payment Method** section, choose **Payment Link / Admin Payment**.

A **Payment Methods** multi-select appears below the payment method selector. Use it to limit which Mollie payment methods the customer can choose when they open the link. Leave all methods deselected to allow every method active on your Mollie account.

### 4. Place the order

Click **Submit Order**.

The order is created in Magento with the status configured in **Status After Creation**. Mollie is not contacted at this point — no transaction exists yet.

### 5. Retrieve and send the payment link

Open the newly created order. The payment link is available in two places:

- **Payment Information block** (if **Add Link to Payment Details** is enabled): the link appears inline in the payment details section with the message template you configured.
- **Payment & Shipping Information → Payment Method**: the link is displayed next to the method name.

Copy the link and send it to the customer by email, SMS, or any other channel. There is no built-in mechanism to send it automatically — use [Second Chance Email](SECOND_CHANCE_EMAIL.md) if you want automated payment reminders after the initial link is sent.

## What Happens When the Customer Opens the Link

The link target at `mollie/checkout/paymentlink` decrypts the order identifier and determines the next action:

| Condition | Result |
|---|---|
| Order is not yet paid | Customer is redirected to Mollie's hosted payment page |
| Order is already in `processing` or `complete` state | Customer sees "Your order has already been paid" and is redirected to the store homepage |
| Payment link has expired | Customer sees "Your payment link has expired" and is redirected to the store homepage |

After the customer completes payment at Mollie, the extension receives a webhook and updates the order status to **Processing** and creates an invoice automatically (matching the behaviour for all other Mollie payment methods).

If the customer cancels payment at Mollie, they can open the link again to retry. The link remains valid until it expires.

## Marking an Order as Paid

When **Allow orders to be marked as paid manually** is enabled, a **Mark as paid** button appears on the order view page for Payment Link orders that are still cancellable.

Clicking **Mark as paid**:

1. Cancels the original Payment Link order
2. Creates a new order from the same items using the check/money order payment method
3. Immediately invoices and sets the new order to **Processing**

Use this when a customer has paid outside of Mollie (for example, by bank transfer confirmed directly) and you need to close the order in Magento.

## Next Steps

- [Second Chance Email](SECOND_CHANCE_EMAIL.md) — Sending automated payment reminders
- [Order Management](ORDER_MANAGEMENT.md) — Order statuses, invoicing, and capture configuration
- [Payment Methods](PAYMENT_METHODS.md) — Overview of all payment methods
- [Headless / GraphQL & REST](HEADLESS.md) — Handling payment links in a headless storefront
