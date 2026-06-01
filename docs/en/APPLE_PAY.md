# Apple Pay Payments

This page covers how to configure Apple Pay in Mollie Payments for Magento 2, including the choice between an external redirect and a direct native integration with buttons on the product page and minicart.

## Prerequisites

- Mollie Payments for Magento 2 is installed and an API key is configured — see [API Keys](API_KEYS.md)
- The store is served over HTTPS — Apple Pay refuses to load on plain HTTP connections
- A live API key is entered in the Mollie configuration, even if the store runs in test mode — see [API Keys](API_KEYS.md)

## Enable Apple Pay

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand **Apple Pay**
3. Set **Enabled** to **Yes**
4. Click **Save Config** and flush the cache

**Important:** Apple Pay is only presented to customers whose device and browser support it and have Apple Pay set up. On all other devices the method is hidden automatically — no customer-facing error is shown.

## Integration Type

The **Integration type** setting controls how the Apple Pay payment sheet is triggered.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Apple Pay**
2. Set **Integration type** to one of:
   - **External** (default) — the customer selects Apple Pay on the standard checkout and is redirected to a Mollie-hosted page where the Apple Pay sheet opens. No additional configuration is required.
   - **Direct** — the Apple Pay sheet opens without leaving your store. A native Apple Pay button appears at checkout, and optionally on the product detail page and in the minicart.
3. Click **Save Config** and flush the cache

**Important:** Direct integration requires merchant validation, which uses the live API key to authenticate with Apple Pay's servers. The live API key must be present even when the store is in test mode. If the live API key is missing, the merchant validation step fails and the payment sheet does not open.

## Direct Integration

When **Integration type** is set to **Direct**, you can place Apple Pay buttons outside the standard checkout flow. The domain validation file (`/.well-known/apple-developer-merchantid-domain-association`) is served automatically by the extension — no manual file deployment is needed.

### Button on the Product Page

Enabling this button lets customers start an Apple Pay session directly from a product detail page, bypassing the cart.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Apple Pay**
2. Set **Enable Button on Product Page** to **Yes**
3. Set **Buy Now Button Style** to one of:
   - **Black** (default) — use on light-coloured backgrounds
   - **White** — use on dark or coloured backgrounds
   - **White Outline** — use on white or light backgrounds that do not provide sufficient contrast with the plain white style
4. Set **Buy Now Button Type** to the label shown inside the button. Available options: **Buy**, **Donate**, **Plain**, **Book**, **Check out** (default), **Subscribe**, **Add money**, **Contribute**, **Order**, **Reload**, **Rent**, **Support**, **Tip**, **Top up**, **None**
5. Click **Save Config** and flush the cache

### Button in the Minicart

Enabling this button places an Apple Pay button in the minicart so customers can pay immediately from anywhere in the store without navigating to checkout.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Apple Pay**
2. Set **Enable Button in minicart** to **Yes**
3. Set **Minicart Button Style** to **Black**, **White**, or **White Outline** (same guidelines as above)
4. Set **Minicart Button Type** to the preferred button label (same options as the product page button; default: **Check out**)
5. Click **Save Config** and flush the cache

### Supported Card Networks

The Apple Pay sheet advertises the card networks the store accepts. The extension always includes Amex, Mastercard, and Visa. Maestro and V Pay are added automatically when the Credit Card payment method's **Capture method** is set to **Autocapture** — see [Order Management](ORDER_MANAGEMENT.md).

## Capture Mode

Apple Pay supports both automatic and manual capture.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Apple Pay**
2. Set **Capture method** to **Autocapture** or **Manual capture**
3. Click **Save Config** and flush the cache

For manual capture, configure **When to capture?** to **On invoice** or **On shipment**, and optionally set a **Capture expiration window**. For automatic capture, you can set a **Capture delay** (with a unit of hours or days) to insert a review window before the payment settles. Full details are in [Order Management](ORDER_MANAGEMENT.md).

## Country and Order Total Restrictions

Restrict Apple Pay to specific countries or order amounts.

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Apple Pay**
2. To limit by country, set **Payment from Applicable Countries** to **Specific Countries** and select the allowed countries in **Payment from Specific Countries**
3. To limit by order amount, enter values in **Minimum Order Total** and/or **Maximum Order Total**
4. Click **Save Config** and flush the cache

## Payment Surcharge

A surcharge can be added to Apple Pay orders to recover processing costs. For configuration details, see [Payment Fee](PAYMENT_FEE.md).

## Domain Validation

Apple requires every domain that presents an Apple Pay button to host a domain validation file at `/.well-known/apple-developer-merchantid-domain-association`. The extension serves this file automatically by fetching it from Mollie and caching it for one week — no manual step is required.

To verify the file is accessible and matches the expected content, run the built-in self-test.

1. Go to **Stores → Configuration → Mollie → General**
2. Expand **Mollie Configuration**
3. Click **Run Self-test**

The self-test checks that the file is reachable at your store's domain and that its content matches the current Mollie certificate. If the check fails, confirm the store URL is publicly accessible and that no server rule (WAF, bot protection, maintenance mode) blocks requests to the `/.well-known/` path.

## Next Steps

- [API Keys](API_KEYS.md) — Entering and managing your live and test API keys
- [Configuration](CONFIGURATION.md) — General settings
- [Order Management](ORDER_MANAGEMENT.md) — Capture modes, invoicing, and refunds
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to payment methods
- [Best Practices](BEST_PRACTICES.md) — Recommended production settings including the self-test
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues
