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

## Install a Pull Request

When a fix for your problem is already merged into an open pull request but has not been released yet, you can apply that pull request to your store as a patch. This is the fastest way to confirm a fix works for your installation.

**Important:** A patch is temporary. Remove it as soon as the fix is part of a release, because updating the extension fails once the patched code no longer matches.

### 1. Install the patches plugin

```bash
composer require cweagans/composer-patches
```

Composer asks whether you trust the plugin to execute code. Answer `y` and press Enter, otherwise no patch is ever applied.

### 2. Download the patch file

Every pull request has a patch representation: take the pull request URL from [github.com/mollie/magento2/pulls](https://github.com/mollie/magento2/pulls) and append `.patch`. Save the result in a `patches` directory in your Magento root.

```bash
mkdir -p patches
curl -L -o patches/1234.patch https://github.com/mollie/magento2/pull/1234.patch
```

Store the file locally instead of referencing the remote URL in `composer.json`. A remote patch is downloaded again on every deployment, so its content can change after you reviewed it.

### 3. Reference the patch in composer.json

Add the patch to the `extra` section of `composer.json`:

```json
{
    "extra": {
        "patches": {
            "mollie/magento2": {
                "Fix for issue #1234": "patches/1234.patch"
            }
        }
    }
}
```

The key is a free-form description that Composer prints when it applies the patch. Use it to record what the patch fixes.

### 4. Apply the patch

```bash
composer update mollie/magento2
```

Composer reinstalls the package and reports each patch it applied. Finish with the usual post-installation steps:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Remove the patch

Delete the entry from the `patches` section in `composer.json` and run `composer update mollie/magento2` again. The package is restored to its released state.

## Composer Reports a Higher Version on Packagist

Composer can refuse to install the extension with a message similar to:

```
Higher matching version 2.40.0 of mollie/magento2 was found in public repository
packagist.org than 2.39.0 in private https://repo.magento.com
```

The extension is published both on Packagist and on the Adobe Commerce Marketplace. Marketplace releases pass through a review process that takes time, so a new version is available on Packagist first. Composer does not silently prefer the public version because that would open the door to a dependency confusion attack, where someone publishes a package under a private vendor namespace on a public repository.

Both packages are the same extension. Pick one of the solutions below.

### Exclude Mollie from repo.magento.com (recommended)

Tell Composer to never resolve Mollie packages through the Marketplace repository. Add `exclude` to the `repo.magento.com` entry in the `repositories` section of `composer.json`:

```json
{
    "repositories": {
        "repo.magento.com": {
            "type": "composer",
            "url": "https://repo.magento.com/",
            "exclude": ["mollie/*"]
        }
    }
}
```

Run `composer require mollie/magento2` again. This is a permanent fix: later updates resolve through Packagist without the warning returning.

### Install the version from the Marketplace

Require the exact version that the message reports for `repo.magento.com`:

```bash
composer require mollie/magento2:2.39.0
```

You end up on an older release and hit the same message on the next update, so use this only when your deployment pipeline requires every package to come from `repo.magento.com`.

### Remove repo.magento.com temporarily

Remove the `repo.magento.com` entry from the `repositories` section, run `composer require mollie/magento2`, and restore the entry afterwards. The resolved version is recorded in `composer.lock`, so the installation succeeds, but the message returns the next time you update the extension.

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
