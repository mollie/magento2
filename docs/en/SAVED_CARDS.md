# Saved Cards

Saved cards lets logged-in customers store a credit card after a successful payment and use it at future checkouts without re-entering their details. Mollie Payments for Magento 2 uses the [Mollie Customers API](https://docs.mollie.com/docs/saving-a-card-for-returning-customers) to store the card as a mandate against a customer record, and passes that mandate ID back on subsequent orders.

## Prerequisites

- The Credit Card payment method is enabled — see [Credit Card Payments](CREDIT_CARD.md)
- Mollie Components is enabled (`Use Mollie Components` set to `Yes`) — saved cards is only available with the embedded card form, not the hosted redirect flow
- A Profile ID is saved under **Stores → Configuration → Mollie → General** — Components requires it
- **Modus** is set to **Live**: saved cards does not work in test mode (see [API Keys](API_KEYS.md))

## Enable Saved Cards

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Card**
2. Set **Enable saved cards** to **Yes**
3. Click **Save Config** and flush the cache — the save-card option does not appear at checkout until the cache is cleared

Saved cards is only offered to logged-in customers. Guest customers never see the save-card checkbox.

Saved cards also requires live mode. While **Modus** is set to **Test**, the save-card checkbox and any previously saved cards are hidden at checkout even when the setting is enabled, and the **Saved cards** page in the customer account is not accessible.

## Customer Consent at Checkout

When a logged-in customer selects Credit Card at checkout, the save-card checkbox and consent text appear below the card entry form.

The flow works as follows:

1. The customer enters their card details using Mollie Components
2. Below the card form, a checkbox labelled **Save your card for faster checkout** is displayed alongside the configured consent text
3. If the customer ticks the checkbox, the extension sends `storeCredentials: true` to Mollie when the order is placed
4. Mollie stores the card as a valid mandate under the customer's Mollie profile and returns the mandate ID
5. On the next visit, the customer's saved cards appear as radio button options above the card entry form — the customer can select a saved card or choose **Use a new card**

When a saved card is selected, the card form is hidden and the order is placed using the stored mandate ID. No new card details need to be entered.

### Customise the Consent Text

The consent text shown alongside the checkbox is configurable per store view. It supports two placeholders that are replaced at runtime:

| Placeholder | Replaced with |
|---|---|
| `{{tradingname}}` | The store name (from **Stores → Configuration → General → Store Information → Store Name**) |
| `{{supportcontact}}` | The general contact email address (from **Stores → Configuration → General → Store Email Addresses → General Contact**) |

To include a hyperlink in the consent text, use Markdown link syntax: `[link text](https://example.com)`.

The default text reads:

> By saving your card, you authorise `{{tradingname}}` to charge your card for future purchases in accordance with our [privacy policy](https://example.com/privacy-policy). To revoke this authorisation, contact us at `{{supportcontact}}`.

To change it:

1. Go to **Stores → Configuration → Mollie → Payment Methods** and expand **Credit Card**
2. Edit the **Consent text** field
3. Click **Save Config** and flush the cache

Update the privacy policy URL in the default text to point to your own policy page before going live.

## Saved Cards with Manual Capture

Mollie only creates the mandate for a saved card once the payment is settled, which happens at capture. With **Capture method** set to **Manual capture**, a card saved at checkout therefore only becomes available to the customer after you invoice or ship that order, depending on the **When to capture?** setting.

Magento Admin shows a notice under the **Enable saved cards** setting when manual capture and saved cards are both enabled. No action is needed: the card appears in the customer's saved cards automatically once the order is captured. See [Credit Card Payments](CREDIT_CARD.md) for capture configuration.

## Managing Saved Cards (My Account)

When saved cards is enabled and the extension runs in live mode, a **Saved cards** link appears in the customer account navigation under **My Account**. Customers reach the page at `/mollie/savedcards/index`.

The page lists all valid credit card mandates on file. Each row shows:

- The card network logo (Visa, Mastercard, American Express, Maestro, Carte Bancaire, or V PAY)
- The card network name and last four digits of the card number
- The card expiry date

To remove a card, the customer clicks **Remove saved card** next to the entry and confirms the prompt. The extension immediately revokes the mandate via the Mollie API. Once revoked, the card no longer appears at checkout and cannot be charged.

The **Saved cards** link only appears in the account navigation when saved cards is enabled and **Modus** is set to **Live**. If you disable the feature or switch to test mode after customers have already saved cards, the link and page become inaccessible, but the underlying mandates in Mollie remain until revoked.

## Admin Visibility

The extension does not add a dedicated saved cards grid to Magento Admin. However, you can view the Mollie customer ID and all mandates for any customer directly in the [Mollie Dashboard](https://www.mollie.com/dashboard) by looking up the customer under **Customers**.

The consent audit log is stored in the `mollie_saved_card_consent` database table. Each row records the order ID, store ID, and the timestamp at which the customer ticked the consent checkbox. This log is retained for as long as the associated sales order exists — if an order is deleted, the consent record is also deleted.

## What Happens When a Card Is Deleted

When a customer removes a saved card from **My Account → Saved cards**, the extension calls `mandates->revokeForId()` on the Mollie API. The revocation is authorised by verifying that the mandate belongs to the currently logged-in customer's Mollie profile — a customer cannot revoke another customer's mandate.

After revocation:

- The card is removed from the customer's saved cards list immediately
- The card no longer appears as an option at checkout
- Any future payment that references the old mandate ID will be rejected by Mollie
- Existing orders that were already charged using the mandate are not affected

## Security and Compliance

- Card data is never stored in Magento. Only the Mollie mandate ID is stored as payment additional information on the order.
- The customer's Mollie profile ID (`cst_...`) is stored in the `mollie_payment_customer` table, linked to the Magento customer record. This is a non-sensitive reference identifier.
- Mandate revocation requires the customer to be logged in. The extension verifies ownership before calling the Mollie API, preventing unauthorised deletions.
- Each delete action from **My Account** is protected by a CSRF form key.
- Explicit written consent is collected at checkout and timestamped before the card is saved. Review your local data protection requirements (such as GDPR) and update the consent text with a link to your privacy policy before enabling the feature.
- The Mollie Customers API is subject to Mollie's own security controls and PCI DSS compliance. Card details are never transmitted through your Magento server.

## Next Steps

- [Credit Card Payments](CREDIT_CARD.md) — Enabling Components, capture mode, and other card settings
- [Configuration](CONFIGURATION.md) — General settings including Profile ID
- [API Keys](API_KEYS.md) — Connecting your Mollie account
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues with card payments
