# Installation: Mollie Payments for Magento 2

This article is for developers and system administrators installing, updating, or verifying Mollie Payments for Magento 2. For a condensed walkthrough that goes straight to placing a test payment, see [Quickstart](QUICKSTART.md).

## System Requirements

Before installing, confirm the environment meets these requirements:

- Magento Open Source or Adobe Commerce **2.4.5** or higher
- PHP **8.1** or higher
- Composer **2.x**
- PHP extension `ext-json`

## Installation via Composer

### 1. Require the package

Run the following command from the Magento root directory:

```bash
composer require mollie/magento2
```

Composer will resolve and download the package along with its dependency `mollie/mollie-api-php`.

### 2. Enable the module

```bash
php bin/magento module:enable Mollie_Payment
```

### 3. Run the upgrade scripts

```bash
php bin/magento setup:upgrade
```

### 4. Compile dependency injection

```bash
php bin/magento setup:di:compile
```

### 5. Deploy static content

Required for production mode. Skip this step on developer mode installations.

```bash
php bin/magento setup:static-content:deploy
```


### 6. Flush the cache

```bash
php bin/magento cache:flush
```

## Verify the Installation

After completing the steps above, confirm the module is active:

```bash
php bin/magento module:status Mollie_Payment
```

The output should read `Module is enabled`.

Check the installed version:

```bash
composer show mollie/magento2 | grep versions
```

In Magento Admin, go to **System → Web Setup Wizard → Component Manager** (or **System → Manage Extensions** on Adobe Commerce Cloud) to confirm `mollie/magento2` appears with the correct version.

## Installation via Magento Marketplace

The extension is also listed on the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com/mollie-magento2.html). Installation still uses Composer. The Marketplace is a discovery and licensing mechanism, not a separate deployment path.

If you have Magento Marketplace authentication keys (available from your Marketplace account under **Access Keys**), configure them in `auth.json` at the Magento root before running Composer:

```json
{
    "http-basic": {
        "repo.magento.com": {
            "username": "<public key>",
            "password": "<private key>"
        }
    }
}
```

Then follow the [Installation via Composer](#installation-via-composer) steps above. The package name and all subsequent commands are identical.

## Update an Existing Installation

If you are upgrading from an older major release, read [Upgrading](UPGRADING.md) before running the Composer update.

### 1. Update the package

```bash
composer update mollie/magento2
```

To update to a specific version:

```bash
composer require mollie/magento2:<version>
```

### 2. Run upgrade and compilation steps

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

Review [Upgrading](UPGRADING.md) and the [changelog](https://github.com/mollie/magento2/releases) before upgrading. Major versions may contain breaking changes that require configuration adjustments.

## Additional Modules

The following packages extend the default functionality of the extension. Each is installed separately via Composer using the same steps above.

| Package | Purpose |
|---|---|
| [`mollie/magento2-hyva-compatibility`](https://github.com/mollie/magento2-hyva-compatibility) | Hyvä Theme compatibility |
| [`mollie/magento2-hyva-checkout`](https://github.com/mollie/magento2-hyva-checkout) | Hyvä Checkout integration |
| [`mollie/magento2-hyva-react-checkout`](https://github.com/mollie/magento2-hyva-react-checkout) | Hyvä React Checkout integration |
| [`mollie/magento2-multishipping`](https://github.com/mollie/magento2-multishipping) | Multi-shipping support |
| [`mollie/magento2-subscriptions`](https://github.com/mollie/magento2-subscriptions) | Subscription payments |

## Next Steps

- [Configuration](CONFIGURATION.md): All general settings
- [API Keys](API_KEYS.md): Connecting your Mollie account
- [Payment Methods](PAYMENT_METHODS.md): Enabling and configuring individual methods
- [Troubleshooting](TROUBLESHOOTING.md): Common installation issues
