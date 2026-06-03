# Google Pay Payments

This page covers how to configure the Google Pay payment method in Mollie Payments for Magento 2.

## Prerequisites

- Mollie Payments for Magento 2 is installed and an API key is configured — see [API Keys](API_KEYS.md)
- Google Pay must be enabled in your Mollie account (via the Mollie Dashboard under **Payment methods**)

## Enable Google Pay

Google Pay is disabled by default. Unlike most Mollie payment methods, it is not controlled by the Methods API, so it will not be enabled automatically when you activate it in the Mollie Dashboard. You must enable it manually in Magento Admin.

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand **Google Pay**
3. Set **Enabled** to **Yes**
4. Click **Save Config** and flush the cache — payment methods are not visible to customers until the cache is cleared

**Important:** Google Pay is only shown in the checkout when the customer's browser supports it and they have a payment method saved in Google Pay. Browsers that do not support the Google Payment Request API will not display this option regardless of the configuration.

## Title

The **Title** field sets the label shown to customers at checkout. The default is `Google Pay`.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Google Pay**
2. Edit the **Title** field
3. Click **Save Config** and flush the cache

## Capture Mode

Google Pay always uses automatic capture. The **Capture method** field is read-only and cannot be switched to manual capture.

### Capture Delay

You can insert a delay between authorisation and settlement to give yourself a window to review or cancel orders before the customer is charged.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Google Pay**
2. Enter a number in **Capture delay**
3. Set **Capture delay unit** to **Hours** or **Days**
4. Click **Save Config** and flush the cache

Leave **Capture delay** empty to settle the payment immediately on authorisation.

## Country and Order Total Restrictions

Restrict Google Pay to specific countries or order amounts.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Google Pay**
2. To limit by country, set **Payment from Applicable Countries** to **Specific Countries** and select the allowed countries in **Payment from Specific Countries**
3. To limit by order amount, enter values in **Minimum Order Total** and/or **Maximum Order Total**
4. Click **Save Config** and flush the cache

## Payment Surcharge

A surcharge can be added to Google Pay orders to recover processing costs. For configuration details, see [Payment Fee](PAYMENT_FEE.md).

## Sort Order

The **Sort Order** field controls the position of Google Pay relative to other payment methods at checkout. Lower numbers appear first.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Google Pay**
2. Enter a number in **Sort Order**
3. Click **Save Config** and flush the cache

## Next Steps

- [Payment Methods](PAYMENT_METHODS.md) — Overview of all available payment methods
- [Order Management](ORDER_MANAGEMENT.md) — Capture, invoicing, and refund behaviour
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to payment methods
- [Configuration](CONFIGURATION.md) — General settings including the Methods API
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues with payment methods
