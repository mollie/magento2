# Mollie for Magento 2 — V3 Codex

This document summarizes the changes on branch `feature/v3` compared to `master` and provides guidance for upgrading to the V3 release.

## Overview
- Minimum PHP raised to >= 8.1 (dropped PHP 7.3 and 7.4).
- Upgraded dependency to mollie/mollie-api-php V3 (typed request/response API).
- Removed Orders API integration; Payments API is now used exclusively.
- Queue-based transaction processing enabled by default, with a new self‑test for queues.
- GraphQL/Web API streamlined for Payments API flow.
- E2E tests migrated from Cypress to Playwright; CI workflows updated.

## Requirements
- PHP: >= 8.1
- mollie/mollie-api-php: ^3.3
- Magento compatibility follows PHP 8.1 baselines (e.g. Magento 2.4.4+).

## Breaking Changes
- Orders API support removed
  - Removed Orders API client and wrappers: all classes under `Model/Client/Orders/*` and `Service/Mollie/Wrapper/OrdersEndpointWrapper.php` are deleted.
  - All transaction creation now goes through the Payments API (`Model/Client/Payments`).
  - Order line handling for BNPL/voucher methods is preserved via `Service/Order/TransactionPart/OrderLines` so those methods still send lines where required using Payments API.
  - Custom code relying on Orders API classes or response shapes must be refactored to use the Payments API flow.

- PHP 7.3/7.4 dropped; PHP 8.1+ required
  - Enforced via `composer.json` and validated by a new self‑test using the Mollie API compatibility checker.

- Success page routing refactor
  - Custom success controller and loading screen removed (`Controller/Checkout/Success.php`, `view/frontend/layout/mollie_checkout_redirect.xml`, `view/frontend/templates/loading.phtml`).
  - Success redirection is handled by `Service/Mollie/Order/SuccessPageRedirect` and the standard Magento success page.
  - Themes or customizations targeting the removed layout/template must be updated.

- API surface
  - GraphQL: `Resolver/Checkout/CreateMollieTransaction.php` removed. Use the `Order.mollie_redirect_url` field to trigger/start or retrieve the redirect URL, and use `Mutation.mollieProcessTransaction` for post‑payment processing. See `etc/schema.graphqls` for the current contract.
  - Wrapper classes for low‑level endpoints (`Service/Mollie/Wrapper/PaymentEndpointWrapper.php`) removed in favor of typed requests via mollie-api-php V3.

## Dependency and Internal Architecture Changes
- mollie/mollie-api-php V3
  - Typed request/response introduced. For example, `Service/Mollie/BuildPaymentRequest` constructs a `CreatePaymentRequest` and uses `Http\Data\Money`, `Http\Data\Address` from the Mollie SDK.
  - The Mollie API client wrapper was simplified; fallback API keys remain supported via `Service/Mollie/Wrapper/MollieApiClientFallbackWrapper`.

- Start transaction flow
  - New `Service/Mollie/StartTransaction` centralizes the start logic and always uses `Model/Client/Payments`.
  - Immediate payment methods (e.g., Apple Pay, Components) trigger transaction processing right away when status is paid/authorized.

- Queue processing default
  - `payment/mollie_general/process_transactions_in_the_queue` defaults to enabled.
  - New self‑test: `Service/Mollie/SelfTests/AreQueuesConfiguredCorrectlyTest` with service `Service/Mollie/AreQueuesConfiguredCorrectly` to verify consumer setup.

- Configuration cleanup and patches
  - Data patches remove legacy configuration for deprecated methods (e.g., Bitcoin, ING Home’Pay).
  - `Setup/Patch/Data/UpdateCustomerReturnUrl` appends placeholders to the custom redirect URL: `?order_id={{ORDER_ID}}&payment_token={{PAYMENT_TOKEN}}&utm_nooverride=1`.

## GraphQL and Web API
- GraphQL contract (highlights)
  - Place order and get redirect URL via `Order.mollie_redirect_url`.
  - Process return flows via `Mutation.mollieProcessTransaction` (returns status and redirect hints).
  - Payment token available via `Order.mollie_payment_token`.
  - Payment methods/issuers/terminals resolvers updated to match Payments API usage.

- Web API
  - `Webapi/StartTransaction` returns the hosted payment `checkoutUrl` or falls back to the success redirect for methods without an HPP.

## Admin UI and Configuration
- Payment Methods section redesigned
  - New blocks and templates for section structure (`Block/Adminhtml/Render/PaymentMethodsSection.php`, `view/adminhtml/templates/system/config/payment-methods/section.phtml`).
  - Extension checker consolidated (`Block/Adminhtml/System/Config/Form/Extension/*`).

- Defaults and toggles
  - Queue processing enabled by default.
  - Methods API enabled by default.

## Testing and CI
- E2E: Cypress → Playwright migration
  - New TypeScript‑based tests under `Test/End-2-end/` with `playwright.config.ts`.
  - Removed Cypress configuration, specs, and webpack setup.

- GitHub Actions
  - Streamlined workflows; introduced separate “fast” and “long” E2E jobs.
  - Removed the legacy unit test workflow; static analysis workflows updated.

## Migration Guide
1) Verify platform requirements
   - Upgrade PHP to 8.1+ and ensure your Magento version supports it.

2) Composer update
   - Ensure `mollie/mollie-api-php` resolves to `^3.3` and install the V3 module update.

3) Replace Orders API usage
   - Remove customizations that call Orders API classes or wrappers; switch to Payments API.
   - If you relied on Orders API order lines for BNPL methods, confirm your totals/lines still match expectations. Lines are now injected for required methods via `Service/Order/TransactionPart/OrderLines` and sent using the Payments API.

4) Queue consumer
   - Ensure the consumer is active: `bin/magento queue:consumers:start mollie.transaction.processor` (or configure cron consumers).
   - Use the self‑test in the admin to validate consumers are registered and allowed.

5) Redirect URL placeholders
   - If you configured a custom redirect URL, ensure it includes `{{ORDER_ID}}` and `{{PAYMENT_TOKEN}}`. The data patch appends these when a value was already present.

6) Theme/layout updates
   - Remove overrides referencing `mollie_checkout_redirect.xml` or `view/frontend/templates/loading.phtml`.

7) GraphQL clients
   - Update to read `Order.mollie_redirect_url` after place‑order and call `mollieProcessTransaction` when returning from Mollie, instead of using the removed CreateMollieTransaction resolver.

## Notable File Removals/Introductions (non‑exhaustive)
- Removed
  - `Model/Client/Orders/*`, `Service/Mollie/Wrapper/OrdersEndpointWrapper.php`
  - `Controller/Checkout/Success.php`, `view/frontend/layout/mollie_checkout_redirect.xml`, `view/frontend/templates/loading.phtml`
  - Cypress E2E test suite under `Test/End-2-end/cypress/*`

- Added
  - `Service/Mollie/StartTransaction.php`, `Service/Mollie/BuildPaymentRequest.php`
  - `Service/Order/TransactionPart/OrderLines.php`
  - Playwright E2E suite under `Test/End-2-end/*` (TypeScript)
  - Queue self‑test utilities (`Service/Mollie/AreQueuesConfiguredCorrectly.php` and corresponding self‑test)

## Notes
- Some legacy translation strings still reference “Orders API”; the runtime flow exclusively uses the Payments API.
- Credit card capture/authorization settings remain configurable; capture logic is refactored but feature‑compatible.

If you need help adapting a customization from Orders API to Payments API, share the relevant code and we’ll suggest a concrete refactor.

