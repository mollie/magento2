# Credit Card Payments

This page covers how to configure the Credit Card payment method, including Mollie Components for embedded card entry, capture mode, and saved cards for returning customers.

## Prerequisites

- Mollie Payments for Magento 2 is installed and an API key is configured — see [API Keys](API_KEYS.md)
- To use Mollie Components, your Profile ID must be saved in the General settings — see [Configuration](CONFIGURATION.md)

## Enable Credit Card Payments

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand **Credit Cards**
3. Set **Enabled** to **Yes**
4. Click **Save Config** and flush the cache — payment methods are not visible to customers until the cache is cleared

## Mollie Components

Mollie Components embeds the card entry form directly on your checkout page so customers never leave your store to enter their card details. Without Components, customers are redirected to a hosted payment page on Mollie's servers.

**Important:** Mollie Components requires a Profile ID. If the Profile ID field in **Stores → Configuration → Mollie → General** is empty, the checkout will fall back to the hosted redirect flow automatically.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Cards**
2. Set **Use Mollie Components** to **Yes**
3. Click **Save Config** and flush the cache

The card form renders inline at the payment step of checkout. Customers enter their card number, expiry date, and CVC without leaving the page.

## Capture Mode

Credit Card payments support a choice between automatic and manual capture. With automatic capture, the payment is settled immediately when the customer authorises it. With manual capture, the payment is authorised but funds are not moved until you trigger the capture from Magento Admin.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Cards**
2. Set **Capture method** to **Autocapture** or **Manual capture**
3. Click **Save Config** and flush the cache

### Manual Capture

When **Manual capture** is selected, configure when the capture is triggered.

1. Set **When to capture?** to one of:
   - **On invoice** — the capture is sent to Mollie when you create an invoice for the order
   - **On shipment** — the capture is sent when you create a shipment; the extension creates the invoice automatically before capturing
2. Optionally, set a **Capture expiration window** to limit how long an authorisation can remain open before it is released automatically

**Important:** An authorisation that expires before it is captured is released automatically by Mollie. Once released, the order cannot be captured and must be cancelled.

### Automatic Capture with a Delay

For **Autocapture**, you can insert a delay between authorisation and settlement to give yourself a window to review or cancel orders.

1. Set **Capture method** to **Autocapture**
2. Enter a value in **Capture delay** and select **Hours** or **Days** as the unit
3. Click **Save Config** and flush the cache

## Saved Cards

When saved cards are enabled, logged-in customers can save their card after a successful payment and use it at future checkouts without re-entering their details. This uses the [Mollie Customers API](https://docs.mollie.com/docs/saving-a-card-for-returning-customers).

### Enable Saved Cards

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Cards**
2. Set **Enable saved cards** to **Yes**
3. Click **Save Config** and flush the cache

A checkbox labelled with the consent text appears at checkout when the customer pays by credit card. The card is saved only if the customer ticks the box.

### Customise the Consent Text

The text shown alongside the save-card checkbox is configurable. It supports two placeholders that are replaced at runtime:

| Placeholder | Replaced with |
|---|---|
| `{{tradingname}}` | The store name |
| `{{supportcontact}}` | The general contact email address |

To include a link, use Markdown link syntax: `[link text](https://example.com)`.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Cards**
2. Edit the **Consent text** field
3. Click **Save Config** and flush the cache

### Managing Saved Cards

Customers can view and delete their saved cards from **My Account → Saved Cards**. Deleting a card revokes the mandate in Mollie so it cannot be used for future payments.

## Country and Order Total Restrictions

Restrict the credit card option to specific countries or order amounts.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Cards**
2. To limit by country, set **Payment from Applicable Countries** to **Specific Countries** and select the allowed countries in **Payment from Specific Countries**
3. To limit by order amount, enter values in **Minimum Order Total** and/or **Maximum Order Total**
4. Click **Save Config** and flush the cache

## Payment Surcharge

A surcharge can be added to credit card orders to recover processing costs. For configuration details, see [Payment Fee](PAYMENT_FEE.md).

## Next Steps

- [Configuration](CONFIGURATION.md) — General settings including Profile ID
- [Saved Cards](SAVED_CARDS.md) — Full guide to the saved cards feature
- [Order Management](ORDER_MANAGEMENT.md) — Capture, invoicing, and refund behaviour
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to payment methods
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues with card payments
