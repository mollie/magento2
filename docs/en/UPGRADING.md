# Upgrading to Version 3

This article is for developers and system administrators upgrading Mollie Payments for Magento 2 from a 2.x release to 3.x. Use it together with the update steps in [Installation](INSTALLATION.md).

## Prerequisites

Before you update the package, confirm the following:

- you are upgrading from a 2.x release to 3.x
- you run custom code against the Mollie extension internals
- you use older Klarna methods or the standalone analytics addon
- you can test the upgrade on a staging environment before applying it to production

## Version 3 Changes

Version 3 changes the platform requirements, payment flow internals, and several operational defaults.

- PHP **8.1** or higher is required
- Magento Open Source / Adobe Commerce **2.4.5** or higher is required
- `mollie/mollie-api-php` was upgraded to v3
- Orders API support was removed; payments now use the Payments API only
- queue-based transaction processing is enabled by default
- manual capture settings are configured per payment method
- legacy Klarna methods were removed in favour of the single **Klarna** method
- the standalone analytics addon was merged into the main module

## Upgrade Steps

Use these steps to apply the package update and verify the result.

1. Review the breaking changes and behaviour changes on this page
2. Update the package:

```bash
composer update mollie/magento2
```

3. Run the Magento upgrade steps:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

4. Use [Installation](INSTALLATION.md#update-an-existing-installation) if you need the full update context
5. In Magento Admin, run the built-in self-test under **Stores → Configuration → Mollie → General → Mollie Configuration**
6. Place at least one end-to-end test payment in the mode you actually use

## Breaking Changes

Review these changes before you update production or merge any custom integration work.

### PHP and Dependency Requirements

Support for PHP 7.3 and 7.4 was dropped. Version 3 requires PHP 8.1+ and `mollie/mollie-api-php` v3.

### Orders API Removal

Orders API support was removed from the extension. Transaction creation now uses the Payments API only.

If you have custom code that depends on removed Orders API classes or wrappers, refactor it before upgrading. The main removed internals are:

- `Model/Client/Orders/*`
- `Service/Mollie/Wrapper/OrdersEndpointWrapper.php`

Any custom logic that relied on Orders API request or response shapes must be updated to use the Payments API flow under `Model/Client/Payments`.

### Legacy Klarna Methods Removed

The old Klarna methods were removed in favour of the unified **Klarna** payment method:

- `klarnapaylater`
- `klarnapaynow`
- `klarnasliceit`

If you still need to refund historic orders created with those legacy methods, do that from the Mollie Dashboard.

### Per-Method Capture Settings

Manual capture is no longer one global setting. Review the capture configuration on each affected payment method after upgrading.

The main affected methods are:

- Billie
- Credit Card
- Klarna
- MobilePay
- Vipps

### Queue Processing Enabled by Default

Webhook processing in the queue is enabled by default in version 3. If the queue consumer is not running, orders are accepted but may never update after payment.

If your platform uses a consumer allowlist, add:

```text
mollie.transaction.processor
```

Then verify queue health with the self-test and the guidance in [Best Practices](BEST_PRACTICES.md) and [Troubleshooting](TROUBLESHOOTING.md).

### Analytics Addon Merged

The standalone `mollie/magento2-analytics` package was merged into the main module.

During `setup:upgrade`, data from `mollie_analytics_analytics` is migrated into `mollie_payment_tracking`, after which the legacy table is removed. The old `Mollie\Analytics\...` namespace is no longer available.

If you previously depended on server-side stripping of the `GA1.2.` prefix from the Google Analytics client ID, handle that on the success page instead:

```javascript
const params = new URLSearchParams(window.location.search);
const raw = params.get('clientId');
const clientId = raw?.split('.').slice(2).join('.') ?? null;
```

## Behaviour Changes

These changes affect how the extension behaves after the upgrade, even when the package update itself succeeds.

### Orders Placed Before the Upgrade

Orders placed on 2.x store a legacy Orders API transaction id (`ord_...`). Version 3 resolves these to the underlying payment automatically, so a 2.x order that is still pending updates correctly once the customer pays. No action is required for status processing.

Capturing a 2.x order on shipment also works: because these orders live on the Orders API, version 3 captures them by shipping the order, which always settles the full authorised amount. Partial captures are not available for 2.x orders.

Authorisation release (cancellation) and refunds are still not handled for these legacy orders. Cancel or refund any 2.x order that needs it from the Mollie Dashboard.

### External Refunds Now Sync Back to Magento

Refunds created directly in the Mollie Dashboard are now detected and converted into Magento credit memos automatically.

### Invoice Creation Can Be Disabled

If invoicing is handled by an ERP or another external system, you can disable automatic invoice creation under **Stores → Configuration → Mollie → Order Management → Advanced**.

### Cancel Order on Checkout Return

Version 3 adds the option to cancel a pending order automatically when the customer navigates back from the Mollie payment page to checkout. This helps free reserved stock more quickly, but it should only be enabled when that trade-off is acceptable.

## Post-Upgrade Checklist

Use this checklist after the package update to confirm the store is ready for production traffic.

After the upgrade:

1. Run the self-test
2. Verify the API keys and **Profile ID**
3. Review per-method capture settings
4. Confirm `mollie.transaction.processor` is allowed and running if queue processing is enabled
5. Check whether automatic invoice creation should remain enabled
6. Verify any headless, PWA, or custom webhook configuration
7. Place a real checkout test and confirm webhook processing, invoicing, and order status updates
8. Cancel or refund any 2.x orders that need it from the Mollie Dashboard

## Next Steps

- [Installation](INSTALLATION.md): Composer update procedure
- [Configuration](CONFIGURATION.md): General configuration after upgrading
- [Order Management](ORDER_MANAGEMENT.md): Capture, invoicing, and refunds
- [Troubleshooting](TROUBLESHOOTING.md): Queue, webhook, and compatibility diagnostics
