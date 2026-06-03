# Payment Methods

This page covers all payment methods supported by Mollie Payments for Magento 2, how to enable and configure them, the settings shared across every method, and the Methods API feature that filters the checkout list automatically.

## Prerequisites

- Mollie Payments for Magento 2 is installed — see [Installation](INSTALLATION.md)
- A live and/or test API key is saved in Magento Admin — see [API Keys](API_KEYS.md)

## Supported Methods

The extension includes the following payment methods out of the box:

| Method | Config key |
|---|---|
| Alma | `mollie_methods_alma` |
| Apple Pay | `mollie_methods_applepay` |
| Bancomat Pay | `mollie_methods_bancomatpay` |
| Bancontact | `mollie_methods_bancontact` |
| Bank Transfer | `mollie_methods_banktransfer` |
| Belfius | `mollie_methods_belfius` |
| Billie | `mollie_methods_billie` |
| Bizum | `mollie_methods_bizum` |
| Blik | `mollie_methods_blik` |
| Credit Card | `mollie_methods_creditcard` |
| SEPA Direct Debit | `mollie_methods_directdebit` |
| EPS | `mollie_methods_eps` |
| Express Components | `mollie_methods_expresscomponents` |
| Giftcard | `mollie_methods_giftcard` |
| Google Pay | `mollie_methods_googlepay` |
| iDEAL / Wero | `mollie_methods_ideal` |
| in3 | `mollie_methods_in3` |
| KBC/CBC | `mollie_methods_kbc` |
| Klarna | `mollie_methods_klarna` |
| MB Way | `mollie_methods_mbway` |
| MobilePay | `mollie_methods_mobilepay` |
| Multibanco | `mollie_methods_multibanco` |
| MyBank | `mollie_methods_mybank` |
| Pay by Bank | `mollie_methods_paybybank` |
| Payconiq | `mollie_methods_payconiq` |
| Payment Link | `mollie_methods_paymentlink` |
| PayPal | `mollie_methods_paypal` |
| Paysafecard | `mollie_methods_paysafecard` |
| Point of Sale (POS) | `mollie_methods_pointofsale` |
| Przelewy24 | `mollie_methods_przelewy24` |
| Riverty | `mollie_methods_riverty` |
| Satispay | `mollie_methods_satispay` |
| Sofort | `mollie_methods_sofort` |
| Swish | `mollie_methods_swish` |
| Trustly | `mollie_methods_trustly` |
| TWINT | `mollie_methods_twint` |
| Vipps | `mollie_methods_vipps` |
| Voucher | `mollie_methods_voucher` |

A method must be active in your Mollie account profile before it appears in the checkout, regardless of the setting in Magento Admin. Log in to the [Mollie Dashboard](https://www.mollie.com/dashboard) and confirm each method is enabled in your profile.

## Enable or Disable a Method

Each payment method is toggled individually. The steps are identical for every method — replace "iDEAL / Wero" in the example with the method name you want to change.

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand the section for the method (for example, **iDEAL / Wero**)
3. Set **Enabled** to **Yes** or **No**
4. Click **Save Config** and flush the cache — changes are not visible to customers until the cache is cleared

**Tip:** The Methods API (see below) can automatically hide a method at checkout when it is not applicable for the customer's country or cart total, so you do not always need to disable methods manually.

## Common Settings

Every method exposes the same core set of fields once **Enabled** is set to **Yes**.

### Title

The **Title** field controls the label shown to the customer at checkout. Edit it to match your store's language or branding.

1. Expand the method section under **Stores → Configuration → Mollie → Payment Methods**
2. Edit the **Title** field
3. Click **Save Config** and flush the cache

### Description

The **Description** field sets the payment description sent to Mollie, which appears in the Mollie Dashboard and on bank statements. The default value is `{ordernumber}`. The following placeholders are available:

| Placeholder | Replaced with |
|---|---|
| `{ordernumber}` | The Magento order increment ID |
| `{storename}` | The store name |

### Country Restrictions

Limit a method to customers in specific countries.

1. Expand the method section under **Stores → Configuration → Mollie → Payment Methods**
2. Set **Payment from Applicable Countries** to **Specific Countries**
3. Select the allowed countries in **Payment from Specific Countries**
4. Click **Save Config** and flush the cache

When the Methods API is enabled, Mollie also enforces country restrictions server-side, so a manually configured list here acts as a secondary guard.

### Order Total Limits

Show a method only when the cart falls within a defined range.

1. Expand the method section under **Stores → Configuration → Mollie → Payment Methods**
2. Enter a value in **Minimum Order Total** and/or **Maximum Order Total** (leave blank for no limit)
3. Click **Save Config** and flush the cache

The values are compared against the order total in the store's base currency.

### Sort Order

The **Sort Order** field determines where a method appears relative to other payment methods in the checkout. Lower numbers appear first. Methods with the same sort order are sorted alphabetically by title.

1. Expand the method section under **Stores → Configuration → Mollie → Payment Methods**
2. Enter a number in **Sort Order**
3. Click **Save Config** and flush the cache

### Payment Surcharge

Each method supports an optional surcharge added to the order total when the customer selects it. For configuration details, see [Payment Fee](PAYMENT_FEE.md).

## The Methods API

The Methods API queries Mollie at checkout time to retrieve only the payment methods that are valid for the customer's billing country and the current cart total. Methods that Mollie would reject are hidden before the customer sees them.

The Methods API is enabled by default. To change the setting:

1. Go to **Stores → Configuration → Mollie → Developer Settings**
2. Expand **Advanced**
3. Set **Enable the methods API** to **Yes** or **No**
4. Click **Save Config** and flush the cache

When the Methods API is disabled, the checkout displays every method you have enabled in Magento Admin. You are then responsible for configuring country and order total restrictions manually to avoid customers selecting a method that Mollie will reject.

**Important:** Disabling the Methods API saves one API call per checkout load but removes the automatic filtering. Only disable it if you have specific country and order total rules configured for every active method.

## Payment Icons

You can show or hide the payment method logo next to the method title at checkout.

1. Go to **Stores → Configuration → Mollie → General → Settings**
2. Set **Show Icons** to **Yes** or **No**
3. Click **Save Config** and flush the cache

## Default Selected Method

You can pre-select a payment method when the customer reaches the payment step. This setting is available at store view scope only.

1. Go to **Stores → Configuration → Mollie → General → Settings**
2. Use the **Store View** switcher (top-left of the configuration page) to select the correct store view
3. Set **Default selected method** to the method you want pre-selected
4. Click **Save Config** and flush the cache

## Testing Methods in Sandbox Mode

Set the extension to test mode and use Mollie's test credentials to simulate payment outcomes without processing real transactions.

1. Set **Modus** to **Test** in **Stores → Configuration → Mollie → General → Mollie Configuration** — see [API Keys](API_KEYS.md)
2. Enable the method you want to test
3. Place a test order and select the method at checkout
4. On the Mollie-hosted payment page, use the test credentials from [Mollie's testing documentation](https://docs.mollie.com/docs/testing) to simulate paid, cancelled, or failed outcomes

The webhook is called after each simulated payment. Confirm the order status in Magento Admin updates correctly. If the status does not change, check the webhook configuration in [Configuration](CONFIGURATION.md).

## Method-Specific Notes

### Bank Transfer

Bank Transfer has an additional **Due Days** field (default: 14) that sets how many days the customer has to complete the transfer before Mollie expires the payment. The valid range is 1 to 100 days.

Bank Transfer also has its own **Status Pending** field. Mollie recommends using a custom order status rather than the default Magento pending status, which can auto-cancel orders before the payment window closes.

### Giftcard and iDEAL / Wero

Both methods support an **Issuer List Style** field that controls how the list of issuers (card brands or banks) is displayed in the checkout:

- **Radio** — displayed as a radio button list
- **Dropdown** — displayed as a dropdown select
- **None** — the issuer is selected on the Mollie-hosted page

### Voucher

The Voucher method requires a **Category** field that classifies the order for Belgian meal, eco, and gift voucher schemes. Set **Category** to **Custom attribute** if your catalogue contains products from mixed categories, then select a product attribute whose values map to `meal`, `eco`, `gift`, or `none`.

### Apple Pay

Apple Pay is only visible to customers whose device has Apple Pay configured and who reach checkout over HTTPS. For the Direct integration (inline button on product pages and minicart) and button style configuration, see [Apple Pay](APPLE_PAY.md).

### Google Pay

Google Pay is disabled by default because it is not controlled by the Methods API. Enable it manually and configure country restrictions to ensure it appears only where Google Pay is supported. For full configuration details, see [Google Pay](GOOGLE_PAY.md).

### Klarna

Klarna uses manual capture by default, triggered on shipment. For order line requirements, capture configuration, and invoice email behaviour, see [Klarna](KLARNA.md).

### Point of Sale

Point of Sale is intended for in-store use and is available to Magento Admin users placing orders from Magento Admin. It supports an **Allowed Customer Groups** restriction in addition to the standard country and order total fields. For full setup instructions, see [Point of Sale](POINT_OF_SALE.md).

### Payment Link

Payment Link is an admin-only method that does not appear in the storefront checkout. It generates a Mollie payment link you can send to a customer manually. See [Payment Link / Admin Payment](PAYMENT_LINK.md) for full configuration and workflow details.

### SEPA Direct Debit

SEPA Direct Debit is disabled by default and requires explicit activation in your Mollie account. Enable it in Admin only after confirming it is active in your Mollie profile.

### Express Components

Express Components is hidden in Magento Admin until it has been activated once via the CLI:

```bash
php bin/magento config:set payment/mollie_methods_expresscomponents/active 1
php bin/magento cache:flush
```

After that, configure it under **Stores → Configuration → Mollie → Payment Methods → Express Components**. For placement, capture behaviour, and live-only limitations, see [Express Components](EXPRESS_COMPONENTS.md).

## Next Steps

- [Apple Pay](APPLE_PAY.md) — Direct integration, product page buttons, and minicart button
- [Google Pay](GOOGLE_PAY.md) — Google Pay setup and configuration
- [Credit Card Payments](CREDIT_CARD.md) — Mollie Components, capture mode, and saved cards
- [Express Components](EXPRESS_COMPONENTS.md) — Express checkout widgets on cart, minicart, and checkout
- [Klarna](KLARNA.md) — Order lines, capture, and invoice configuration
- [Point of Sale](POINT_OF_SALE.md) — In-store payment setup
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to any payment method
- [Configuration](CONFIGURATION.md) — General settings, locale, and order status
- [API Keys](API_KEYS.md) — Switching between test and live mode
