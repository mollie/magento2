# API Keys

This article explains how to locate your Mollie API keys, enter them in Magento, switch between test and live mode, and configure separate keys per website in a multi-store setup.

## Locate Your API Keys

1. Log in to the [Mollie Dashboard](https://www.mollie.com/dashboard)
2. Go to **Developers**
3. Click **Create access token**
4. Enter a description for the key
5. Select **Standard API key**
6. Select the profile to link the key to
7. Choose the API mode: **Live** or **Test**
8. Copy the generated key

## Enter Keys in Magento Admin

1. Go to **Stores → Configuration → Mollie → General**
2. Expand **Mollie Configuration**
3. Paste the **Test API key** into the *Test API Key* field
4. Paste the **Live API key** into the *Live API Key* field
5. Set **Modus** to **Test** or **Live** depending on which key should be active
6. Click **Save Config**
7. Go to **System → Cache Management** and click **Flush Magento Cache**

![Mollie Configuration fields showing Modus, Test API Key, Live API Key, and Profile ID](../images/api-keys-mollie-configuration.png)

After saving, the extension validates the key against the Mollie API and auto-populates the **Profile ID** field. The field below each key shows the first five and last four characters of the currently stored key for verification.

## Switch Between Test and Live Mode

The **Modus** setting controls which key is used for all transactions. Changing the modus alone is sufficient; both keys remain stored.

1. Go to **Stores → Configuration → Mollie → General → Mollie Configuration**
2. Set **Modus** to **Test** or **Live**
3. Click **Save Config** and flush the cache

In test mode, no real payments are processed. Use [Mollie's test credentials](https://docs.mollie.com/docs/testing) to simulate payment outcomes.

## Multi-Store Configuration

API keys can be set at the default, website, or store view scope. This allows different Mollie accounts or profiles per website.

1. In Magento Admin, use the **Scope** switcher at the top of the configuration page to select the website or store view to configure

   ![Scope switcher showing "Default Config"](../images/api-keys-scope-switcher.png)

   Click it to reveal the available websites and store views:

   ![Scope dropdown open showing Main Website and store views](../images/api-keys-scope-dropdown-open.png)

2. Go to **Stores → Configuration → Mollie → General → Mollie Configuration**
3. Uncheck **Use Default** next to the API key fields
4. Enter the keys for this scope
5. Set the **Modus** for this scope
6. Click **Save Config** and flush the cache

Repeat for each website that requires separate keys.

## Rotate or Replace a Key

Generate a new key in the Mollie Dashboard and update it in Magento. The extension retains the previous key as a fallback so webhooks in transit are not dropped during the switchover.

1. Generate a new key in the Mollie Dashboard under **Developers → API keys**
2. In Magento Admin, go to **Stores → Configuration → Mollie → General → Mollie Configuration**
3. Clear the existing key field and paste the new key
4. Click **Save Config** and flush the cache

## Next Steps

- [Configuration](CONFIGURATION.md) — All general settings
- [Payment Methods](PAYMENT_METHODS.md) — Enabling individual payment methods
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues including API key errors
