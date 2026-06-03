# Point of Sale Payments

Point of Sale (POS) lets a customer place an order online and pay on a physical Mollie Terminal in your store or at the counter. The order is created in Magento and the payment request is sent directly to the terminal you select; no redirect to an external payment page takes place.

## Prerequisites

- Mollie Payments for Magento 2 is installed and a live API key is configured — see [API Keys](API_KEYS.md)
- At least one Mollie Terminal is registered and active in your [Mollie Dashboard](https://www.mollie.com/dashboard) under **Point of Sale → Terminals**
- POS payments require a live API key; the terminal integration is not available in test mode

## Enable Point of Sale

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand **Point Of Sale (POS)**
3. Set **Enabled** to **Yes**
4. Click **Save Config** and flush the cache

## Configure the Payment Method

With POS enabled, the following fields appear in **Stores → Configuration → Mollie → Payment Methods → Point Of Sale (POS)**.

### Title and Description

1. Edit the **Title** field to change the label shown to customers at checkout (default: `Point Of Sale (POS)`)
2. Edit the **Description** field to set the payment description sent to Mollie with each transaction — use `{ordernumber}` to include the Magento order number automatically

### Restrict to Customer Groups

POS is intended for staff-assisted or in-store scenarios, so it is typically restricted to specific customer groups rather than shown to all visitors.

1. Set **Payment from Applicable Customer Groups** to the groups that should see the POS option at checkout
2. Leave the field empty to hide POS from all customers (useful if you create POS orders exclusively from Magento Admin)

Customer group filtering is enforced both at checkout and via the REST API. A customer not in an allowed group will not see the payment method, and the checkout will block the order if the method is selected via an API call.

### Country and Order Total Restrictions

1. Set **Payment from Applicable Countries** to **All Allowed Countries** or **Specific Countries**
2. If you selected **Specific Countries**, choose the allowed countries in **Payment from Specific Countries**
3. Enter values in **Minimum Order Total** and/or **Maximum Order Total** to limit the order amounts for which POS is available
4. Click **Save Config** and flush the cache

### Capture Method

The capture method controls when funds are settled after the customer pays on the terminal.

1. Set **Capture method** to **Autocapture** or **Manual capture**
   - **Autocapture** — the payment is captured immediately when the customer confirms on the terminal
   - **Manual capture** — the payment is authorised and you trigger the capture later from Magento Admin, either on invoice creation or on shipment
2. Click **Save Config** and flush the cache

For most POS scenarios, **Autocapture** is the correct choice because the customer is physically present and goods are handed over immediately.

## Terminal Selection at Checkout

When a customer selects Point of Sale at checkout, the extension fetches the list of active terminals from the Mollie API and displays them as a radio button list. The terminal list shows the brand, model, serial number, and description for each device.

The customer (or the staff member assisting them) selects the terminal to use before placing the order. The extension stores the last-used terminal in the browser's local storage and pre-selects it on the next visit, so returning customers do not need to choose again if only one terminal is in use.

If only one terminal is active on your Mollie account, it is selected automatically and no choice is presented.

**Important:** A terminal must be selected before the order can be placed. If no terminal is selected, the Place Order button stays disabled.

## Creating POS Orders from Magento Admin

POS orders can also be created directly in Magento Admin, for example when staff take orders by phone and the customer will pay in person.

1. Go to **Sales → Orders** and click **Create New Order**
2. Select the customer and store, add products, and fill in the address
3. In the **Payment Method** section, select **Point Of Sale (POS)**
4. Select the terminal from the list of active devices
5. Click **Submit Order**

The extension submits the order and immediately sends the payment request to the selected terminal. The terminal screen activates and prompts the customer to present their card or device.

## Payment Confirmation Flow

After the order is placed, the customer is shown a waiting page that polls the Mollie API for the payment status. The page displays the current status and updates automatically.

- While the payment is pending, the page shows the current status (for example, `pending` or `authorized`)
- When the payment is confirmed, the customer is redirected to the standard order success page
- If the payment is cancelled on the terminal, a **Retry** button appears so the customer can attempt the payment again on the same or a different terminal without re-entering their order details

The retry flow restores the customer's cart and redirects them back to the payment step of checkout.

## Order Management

### Invoicing and Capture

POS orders follow the same invoicing rules as other Mollie payment methods. With **Autocapture**, the extension creates the Magento invoice automatically when it receives the payment confirmation webhook.

With **Manual capture**:

1. Go to **Sales → Orders** and open the POS order
2. Click **Invoice**
3. Review the invoice and click **Submit Invoice** — this triggers the capture request to Mollie
4. The funds are settled and the invoice is marked as paid

### Refunds

Refunds for POS orders are processed through standard Magento credit memo creation.

1. Go to **Sales → Orders** and open the order
2. Open the **Invoices** tab, click the invoice, then click **Credit Memo**
3. Adjust quantities or amounts as needed
4. Click **Refund** — do not use **Refund Offline**, as that skips the API call to Mollie

The refund is sent to the Mollie API and credited to the card or account the customer used on the terminal. The timeline for the refund to appear depends on the customer's bank.

Partial refunds per invoice line are supported.

## Limitations

- POS payments require a live API key. Test mode is not supported because terminals are physical devices that cannot be simulated.
- The terminal list is fetched live from the Mollie API at checkout. If the Mollie API is unreachable, no terminals are returned and the POS method cannot be used.
- POS is not compatible with headless or GraphQL-only checkouts that do not implement terminal selection. The `mollie_available_terminals` field is available on the GraphQL `AvailablePaymentMethod` type for custom implementations — see [Headless](HEADLESS.md).
- Saving cards and subscription payments are not supported for POS transactions.
- Apple Pay and Google Pay on the terminal are controlled by the terminal's firmware and the customer's device. These are not separately configurable in Magento.

## Next Steps

- [Order Management](ORDER_MANAGEMENT.md) — Capture modes, invoicing, and refund behaviour
- [API Keys](API_KEYS.md) — Switching between test and live mode
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to POS payments
- [Headless](HEADLESS.md) — Terminal selection in headless checkouts
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues with payment methods
