# Mollie Magento 2 - Version 3.0 Changelog

## Overview

Version 3.0 represents a major update to the Mollie Magento 2 extension, focusing on modernization, improved code quality, and streamlined architecture. This release includes several breaking changes and requires careful migration planning.

**Summary Statistics:**
- 673 files changed
- 7,076 insertions
- 18,202 deletions
- Net reduction of 11,126 lines of code

---

## Breaking Changes

### 1. PHP Version Requirements

**Dropped Support:**
- PHP 7.3 (EOL: December 2021)
- PHP 7.4 (EOL: November 2022)

**New Minimum Requirement:**
- PHP 8.1 or higher

**Migration Path:**
Ensure your Magento 2 installation is running PHP 8.1 or higher before upgrading to version 3.0.

```json
// composer.json - Before
"php": ">=7.3"

// composer.json - After
"php": ">=8.1"
```

### 2. Mollie API PHP Library Update

**Updated Dependency:**
- From: `mollie/mollie-api-php: ^2.65`
- To: `mollie/mollie-api-php: ^3.3`

This is a major version upgrade of the underlying Mollie API client library with improved type safety and modern PHP features.

### 3. Complete Removal of Orders API Support

**What Was Removed:**

The Mollie Orders API integration has been completely removed from the extension. All payment processing now exclusively uses the Mollie Payments API.

**Deleted Components (109 files):**
- `Model/Client/Orders.php` and all related processor classes
- `Model/Client/Orders/OrderProcessors.php`
- `Model/Client/Orders/ProcessTransaction.php`
- All order processors:
  - `AddAdditionalInformation.php`
  - `CancelledProcessor.php`
  - `ExpiredProcessor.php`
  - `LastPaymentStatusIsFailure.php`
  - `PaymentLinkPaymentMethod.php`
  - `SaveCardDetails.php`
  - `SendConfirmationEmailForBanktransfer.php`
  - `SuccessfulPayment.php`
- `Service/Mollie/Order/UsedMollieApi.php`
- `Service/Mollie/Wrapper/OrdersEndpointWrapper.php`
- `Service/Order/PartialInvoice.php`
- Various observers related to Orders API:
  - `OrderCancelAfter.php`
  - `SalesOrderCreditmemoSaveAfter.php`
  - `SalesOrderShipmentSaveBefore/CreateMollieShipment.php`
  - `SalesOrderShipmentTrackSaveAfter.php`

**Why This Change:**

The Payments API provides all necessary functionality with better reliability and simpler integration. Maintaining both APIs added unnecessary complexity and maintenance burden.

**Migration Impact:**

Existing orders created with the Orders API will continue to function for refunds and order management, but all new transactions will use the Payments API exclusively.

---

## Major Features & Improvements

### 1. Per-Method Capture Mode Configuration

**New Feature:**

Payment methods that support authorization/capture now have individual capture mode settings instead of a single global setting.

**Affected Payment Methods:**
- Credit Card
- Billie
- Klarna
- Klarna Pay Later
- Klarna Pay Now
- Klarna Slice It
- MobilePay
- Vipps

**Configuration:**

Each supported payment method now has a "Capture method" field with two options:
- **Automatic**: Capture payment immediately upon authorization (default)
- **Manual**: Hold the authorization and capture later (e.g., upon shipment)

**Code Changes:**
- Added: `Model/Adminhtml/Source/CaptureMode.php`
- Removed: Global `enable_manual_capture` configuration setting
- Configuration: `payment/mollie_methods_{method}/capture_mode`

**Example Configuration (Credit Card):**
```xml
<field id="capture_mode" translate="label comment" type="select" sortOrder="50">
    <label>Capture method</label>
    <config_path>payment/mollie_methods_creditcard/capture_mode</config_path>
    <source_model>Mollie\Payment\Model\Adminhtml\Source\CaptureMode</source_model>
</field>
```

### 2. Enhanced Admin Configuration UI

**New Admin Components:**

Several new UI components have been added to improve the configuration experience:

1. **Payment Methods Section Header**
   - `Block/Adminhtml/Render/PaymentMethodsSection.php`
   - Custom frontend model for payment methods configuration section
   - Better visual organization of payment method settings

2. **Payment Method Group Cards**
   - `Block/Adminhtml/System/Config/GroupFrontendModel.php`
   - Individual payment methods now display as styled cards
   - Improved visual hierarchy and readability

3. **Header Label Component**
   - `Block/Adminhtml/Render/HeaderLabel.php`
   - Consistent styling for section headers

4. **Unified Extension Checker**
   - Renamed and improved: `Block/Adminhtml/System/Config/Form/Apikey/Checker.php` → `Block/Adminhtml/System/Config/Form/Extension/Checker.php`
   - Better credential validation and extension compatibility checking

**Visual Improvements:**
- Added: `view/adminhtml/web/css/source/_method-card.less` (151 lines of new styles)
- Enhanced: Method configuration cards with better spacing and visual organization
- New template: `view/adminhtml/templates/system/config/payment-methods/section.phtml`

### 3. Queue Configuration Validation

**New Feature:**

Added automatic detection and validation of message queue configuration to help diagnose common setup issues.

**New Components:**
- `Model/Adminhtml/Comment/AreQueuesConfiguredCorrectly.php`
- `Service/Mollie/AreQueuesConfiguredCorrectly.php`
- `Service/Mollie/SelfTests/AreQueuesConfiguredCorrectlyTest.php`

**What It Does:**
- Checks if message queues are properly configured
- Validates that required queue consumers are running
- Displays warnings in admin if queue configuration is incorrect
- Added to self-test functionality

**Configuration:**
New setting: `payment/mollie_general/process_transactions_in_the_queue` (default: enabled)

### 4. Transaction Processing Refactoring

**Architectural Improvement:**

Transaction initialization logic has been extracted into a dedicated service class for better maintainability and testability.

**New Service:**
- `Service/Mollie/StartTransaction.php`

**Changes:**
- Centralized transaction start logic
- Improved error handling
- Better separation of concerns
- Easier to test and maintain

**Related:**
- `Service/Mollie/BuildPaymentRequest.php` - Extracted payment request building logic
- `Service/Order/TransactionPart/OrderLines.php` - Improved order line handling

---

## Testing Infrastructure Updates

### 1. Cypress to Playwright Migration

**Complete Testing Framework Replacement:**

End-to-end tests have been migrated from Cypress to Playwright for better reliability, speed, and modern TypeScript support.

**What Changed:**
- Removed: All Cypress test files and configuration
- Added: Complete Playwright test suite in TypeScript
- Improved: Test reliability and execution speed
- Better: Cross-browser testing support

**New Test Files:**
- `Test/End-2-end/playwright.config.ts`
- `Test/End-2-end/global-setup.ts`
- All tests converted to `.spec.ts` format
- TypeScript-based page objects and action classes

**New Test Structure:**
```
Test/End-2-end/
├── tests/
│   ├── methods/          # Payment method tests
│   ├── api/              # API tests
│   ├── backend/          # Admin functionality tests
│   └── auth/             # Authentication setup
├── support/
│   ├── pages/            # Page object models
│   ├── actions/          # Reusable actions
│   └── composite/        # Composite actions
└── playwright.config.ts  # Playwright configuration
```

**GitHub Workflows:**
- Split into fast and long-running test suites
- `.github/workflows/end-2-end-test-fast.yml` (new)
- `.github/workflows/end-2-end-test-long.yml` (renamed)

### 2. Unit Testing Updates

**Removed:**
- `.github/workflows/unit-test.yml`

**Reason:** Consolidated into integration test workflows for better coverage and reliability.

---

## Code Quality Improvements

### 1. PHP 8.1+ Features Adoption

**Constructor Property Promotion:**

Extensive use of PHP 8.1 constructor property promotion throughout the codebase:

```php
// Before (PHP 7.3)
private $config;
private $helper;

public function __construct(
    Config $config,
    Helper $helper
) {
    $this->config = $config;
    $this->helper = $helper;
}

// After (PHP 8.1)
public function __construct(
    private readonly Config $config,
    private readonly Helper $helper
) {
}
```

**Improved Type Hints:**
- Added strict return type declarations across the codebase
- Better nullable type handling with `?Type` syntax
- Union types where appropriate
- Property type declarations

### 2. Code Cleanup

**Statistics:**
- **11,126 fewer lines of code** (net reduction)
- Removed redundant code paths
- Eliminated Orders API complexity
- Streamlined payment processing flow

**Specific Improvements:**
- Better separation of concerns
- Reduced cyclomatic complexity
- Improved error handling
- More consistent code style

### 3. PHPStan Level Increase

**Static Analysis:**

Stricter PHPStan analysis levels implemented to catch more potential issues during development.

**Commits:**
- "Improvement: Increase PHPStan level"
- "Fix PHPStan errors"
- Ongoing type safety improvements

---

## Configuration Changes

### Removed Configuration Options

1. **`enable_manual_capture` (Global Setting)**
   - **Removed From:** `payment/mollie_general/enable_manual_capture`
   - **Replaced By:** Per-method `capture_mode` setting
   - **Migration:** If you had manual capture enabled globally, you'll need to configure it per payment method

2. **`enable_methods_api` (Methods API Toggle)**
   - **Removed From:** `payment/mollie_general/enable_methods_api`
   - **Reason:** Methods API is now always used for better payment method availability detection
   - **Migration:** No action needed - feature is always active

### New Configuration Options

1. **Per-Method Capture Mode**
   - **Path:** `payment/mollie_methods_{method}/capture_mode`
   - **Options:** `automatic` (default), `manual`
   - **Available For:** Credit Card, Billie, Klarna (all variants), MobilePay, Vipps

2. **Process Transactions in Queue**
   - **Path:** `payment/mollie_general/process_transactions_in_the_queue`
   - **Default:** `1` (enabled)
   - **Purpose:** Control whether transactions are processed via message queues

3. **Second Chance Payment Method**
   - **Path:** `payment/mollie_general/second_chance_use_payment_method`
   - **Purpose:** Specify which payment method to use when customer returns via second chance email

### Modified Configuration Structure

**Payment Methods Section:**
- Now uses custom frontend model for improved UI
- Better visual organization with method cards
- Enhanced help text and field descriptions
- Improved grammatical consistency across all labels and comments

**System.xml Updates:**
- Corrected grammatical errors in comments
- Consistent capitalization (URL instead of url, API instead of Api)
- Better structured field dependencies
- Improved help text clarity

---

## API & Integration Changes

### GraphQL Updates

**Modified Resolvers:**
- `GraphQL/Resolver/Checkout/PlaceOrderAndReturnRedirectUrl.php`
- `GraphQL/Resolver/Checkout/PaymentToken.php`
- `GraphQL/Resolver/General/MolliePaymentMethods.php`

**Removed:**
- `GraphQL/Resolver/Checkout/CreateMollieTransaction.php` (consolidated into other resolvers)

**Changes:**
- Payments API only (no more Orders API logic)
- Improved error handling
- Better type safety
- Consistent with REST API changes

### Webapi Changes

**Updated Interfaces:**
- `Api/Webapi/PaymentTokenRequestInterface.php`
- `Api/Webapi/StartTransactionRequestInterface.php`

**Changes:**
- Aligned with Payments API structure
- Removed Orders API specific methods
- Improved parameter validation

---

## Developer Experience Improvements

### 1. Better Error Messages

**Improved Diagnostics:**
- Queue configuration validation with helpful error messages
- API credential checking with detailed feedback
- Self-test functionality expanded

### 2. Code Organization

**Service Layer Improvements:**
- Better organized service classes
- Clear responsibility separation
- Easier to understand payment flow

**Example:**
```
Service/Mollie/
├── StartTransaction.php          # New: Centralized transaction start
├── BuildPaymentRequest.php       # New: Payment request builder
├── AreQueuesConfiguredCorrectly.php  # New: Queue validation
└── SelfTests/                    # Expanded self-test suite
```

### 3. Documentation

**Improved Comments:**
- Better PHPDoc blocks
- More descriptive parameter names
- Clearer method purposes

---

## Migration Guide

### Pre-Upgrade Checklist

1. **PHP Version:**
   - ✅ Verify running PHP 8.1 or higher
   - ✅ Check all PHP extensions are compatible

2. **Backup:**
   - ✅ Complete database backup
   - ✅ Complete file system backup
   - ✅ Test restore procedure

3. **Testing Environment:**
   - ✅ Test upgrade in staging/development first
   - ✅ Verify all payment methods work correctly
   - ✅ Test order processing end-to-end

4. **Configuration Review:**
   - ✅ Document current payment method configurations
   - ✅ Note any custom capture/authorization settings
   - ✅ Review queue consumer setup

### Upgrade Steps

1. **Update Composer:**
   ```bash
   composer require mollie/magento2:^3.0
   ```

2. **Run Magento Upgrade:**
   ```bash
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   php bin/magento cache:flush
   ```

3. **Configure Capture Mode:**
   - Navigate to: Stores > Configuration > Sales > Payment Methods > Mollie
   - For each payment method that previously used manual capture:
     - Open payment method configuration
     - Set "Capture method" to "Manual"
     - Save configuration

4. **Verify Queue Configuration:**
   - Check self-test results in admin
   - Ensure queue consumers are running:
     ```bash
     php bin/magento queue:consumers:list
     ```
   - Start required consumers if not already running

5. **Test Payment Processing:**
   - Place test orders with each active payment method
   - Verify authorization/capture behavior
   - Test refund functionality
   - Verify webhook processing

### Post-Upgrade Validation

1. **Functional Testing:**
   - ✅ Place orders with each payment method
   - ✅ Test guest and customer checkouts
   - ✅ Verify order status updates
   - ✅ Test refund creation
   - ✅ Verify invoice generation

2. **Configuration Verification:**
   - ✅ Check all payment methods are properly configured
   - ✅ Verify capture mode settings are correct
   - ✅ Test webhook endpoints are accessible
   - ✅ Validate API credentials

3. **Performance Monitoring:**
   - ✅ Monitor checkout performance
   - ✅ Check queue processing
   - ✅ Review error logs for issues

---

## Known Issues & Considerations

### 1. Historical Orders API Transactions

**Status:** Orders created with the Orders API before upgrade will continue to work for refunds and status updates, but new transactions exclusively use the Payments API.

**Recommendation:** No action needed - this is handled automatically.

### 2. Custom Integrations

**Impact:** If you have custom code that:
- Directly references `Model/Client/Orders` classes
- Implements custom order processors
- Extends Orders API functionality

**Action Required:** Refactor custom code to use Payments API equivalents.

### 3. Capture Mode Configuration

**Migration:** The global `enable_manual_capture` setting is removed. You must configure capture mode per payment method if you were using manual capture.

**Default Behavior:** All payment methods default to automatic capture. Review and adjust as needed.

---

## Compatibility

### Magento Versions

**Supported:**
- Magento 2.3.3 and higher
- Adobe Commerce 2.3.3 and higher

**Requirements:**
- PHP 8.1 or higher
- All standard Magento 2 module dependencies

### Browser Support (E2E Tests)

With Playwright migration, improved support for:
- Chrome/Chromium
- Firefox
- WebKit (Safari)
- Mobile browsers via device emulation

---

## Performance Improvements

### 1. Reduced Code Footprint

**Impact:**
- 11,126 fewer lines of code
- Reduced memory usage
- Faster class loading

### 2. Simplified Payment Flow

**Benefits:**
- Single API path (Payments API only)
- Fewer conditional branches
- More predictable behavior
- Easier debugging

### 3. Better Queue Processing

**Improvements:**
- Enhanced queue configuration validation
- Better error handling in async processing
- Improved transaction reliability

---

## Security Improvements

### 1. Type Safety

**PHP 8.1 Features:**
- Strict type declarations throughout
- Better null safety
- Reduced risk of type-related bugs

### 2. Updated Dependencies

**Mollie API PHP v3:**
- Latest security patches
- Improved input validation
- Better error handling

### 3. Removed Legacy Code

**Impact:**
- Eliminated unused code paths
- Reduced attack surface
- Simpler security auditing

---

## Credits

This major version update includes contributions and improvements from:
- Core Mollie development team
- Community feedback and testing
- Automated testing improvements
- Code quality enhancements

---

## Support & Resources

### Documentation

- [Installation Guide](https://github.com/mollie/magento2/wiki/Installation-using-Composer)
- [Configuration Guide](https://github.com/mollie/magento2/wiki/Configure-the-extension)
- [Troubleshooting](https://github.com/mollie/magento2/wiki/Troubleshooting)

### Getting Help

- **Issues:** [GitHub Issues](https://github.com/mollie/magento2/issues)
- **Discussions:** [GitHub Discussions](https://github.com/mollie/magento2/discussions)
- **Mollie Support:** [support@mollie.com](mailto:support@mollie.com)

### Contributing

Contributions are welcome! Please read our contributing guidelines before submitting pull requests.

---

## Appendix: Detailed File Changes

### Added Files (Notable)

**Admin UI Components:**
- `Block/Adminhtml/Render/HeaderLabel.php`
- `Block/Adminhtml/Render/PaymentMethodsSection.php`
- `Block/Adminhtml/System/Config/Form/Extension/Checker.php`
- `Block/Adminhtml/System/Config/GroupFrontendModel.php`

**Configuration:**
- `Model/Adminhtml/Source/CaptureMode.php`
- `Model/Adminhtml/Comment/AreQueuesConfiguredCorrectly.php`

**Services:**
- `Service/Mollie/StartTransaction.php`
- `Service/Mollie/BuildPaymentRequest.php`
- `Service/Mollie/AreQueuesConfiguredCorrectly.php`
- `Service/Order/TransactionPart/OrderLines.php`

**Testing:**
- Complete Playwright test suite (50+ files)

### Deleted Files (Notable)

**Orders API (Complete Removal):**
- All files in `Model/Client/Orders/`
- All order processors
- Orders API wrappers and services
- Related observers and event handlers

**Testing:**
- All Cypress test files
- Legacy unit test workflow

**UI:**
- `Block/Loading.php` (loading screen component)
- `view/frontend/templates/loading.phtml`
- Related loading screen styles

---

**Document Version:** 1.0
**Last Updated:** 2024
**Target Release:** v3.0.0
