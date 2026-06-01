# Troubleshooting

This article covers the built-in diagnostic tools and solutions to the most common problems you will encounter with Mollie Payments for Magento 2.

## Built-in Self-Test

The self-test runs a series of automated checks against your installation and reports errors and warnings inline. Run it after initial setup and whenever you suspect a configuration problem.

1. Go to **Stores → Configuration → Mollie → General → Mollie Configuration**
2. Click **Run Self-test**
3. Review the results in the panel that appears below the button

The self-test checks the following:

- PHP version and the `ext-json` extension are present
- The Mollie PHP API client is installed (confirms a Composer-based installation)
- The webhook endpoint at `/mollie/checkout/webhook/` is publicly reachable and returns `OK`
- Webhooks are enabled when running in test mode (a common oversight on staging environments)
- The `mollie.transaction.processor` message queue consumer is configured and allowed to run
- All required extension attributes are present (confirms `setup:di:compile` has run)
- The bank transfer pending status is not set to the generic `pending_payment` status
- The Apple Pay domain validation file is present and matches Mollie's copy
- GeoIP modules that may interfere with webhook routing are detected
- If the Hyvä Theme is active, the required Mollie compatibility modules are installed

Errors block correct operation and must be resolved. Warnings point to configuration choices that may cause problems but do not always do so.

**Important:** Run the self-test from a publicly accessible server. The webhook reachability check makes an outbound HTTP request to your own store, so it cannot succeed from localhost or a server behind a VPN that is not exposed to the internet.

## Debug Logging

The extension writes detailed request and response data to `var/log/mollie.log`. On a fresh installation, **Debug requests** is enabled by default, so disable it on production stores unless you actively need it for troubleshooting.

### Enable debug logging

1. Go to **Stores → Configuration → Mollie → General → Debug & Logging**
2. Set **Debug requests** to **Yes**
3. Click **Save Config**

No cache flush is required. Logging starts immediately for subsequent requests.

### Read the log in the Admin

When logging is enabled, a **Show log** panel appears below the **Debug requests** setting. It displays the most recent 100 entries directly in the browser without requiring server access.

### Access the raw log file

The log is written to `<magento-root>/var/log/mollie.log`. You can tail it from the command line:

```bash
tail -f var/log/mollie.log
```

### Anonymise log data

If you need to share log output with a third party, enable **Anonymize debug requests** (visible when **Debug requests** is set to **Yes**). This replaces personally identifiable values (names, email addresses, addresses) before they are written to the log.

### Download the debug bundle

The debug bundle packages the log file together with redacted configuration and environment metadata into a single archive, suitable for sharing with Mollie support.

1. Go to **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Click **Download debug information**

The download is a `.tar.gz` archive. Sensitive values (API keys, encryption key, passwords) are redacted automatically.

## Common Problems

### Payment methods not showing at checkout

Payment methods do not appear at checkout when the extension cannot confirm that a method is active and available for the current cart.

Check the following in order:

1. Confirm the extension is enabled: go to **Stores → Configuration → Mollie → General → Mollie Configuration** and verify **Enabled** is set to **Yes**
2. Confirm an API key is saved and the mode (**Modus**) is set correctly — see [API Keys](API_KEYS.md)
3. Go to **Stores → Configuration → Mollie → Payment Methods** and confirm the specific payment method is enabled
4. Flush the cache: go to **System → Cache Management** and click **Flush Magento Cache**. Payment method visibility changes are not visible to customers until the cache is cleared
5. If **Enable the methods API** is set to **Yes** under **Stores → Configuration → Mollie → Developer Settings → Advanced**, the extension filters methods based on the customer's country and cart total. The method may be legitimately unavailable for the current cart. Disable the methods API temporarily to confirm whether it is the cause, then re-enable it and adjust the Mollie profile's method settings in the [Mollie Dashboard](https://www.mollie.com/dashboard)
6. Check that the method is active on your Mollie profile in the Dashboard. A method enabled in Magento but not activated on the Mollie profile will not be returned by the API

### Webhooks not being received

Mollie sends a POST request to your webhook URL when a transaction status changes. If webhooks are not received, orders do not update automatically.

1. Run the self-test (see above). The webhook reachability check tells you whether Mollie can reach the endpoint and what HTTP status it returned
2. Confirm the webhook URL is publicly accessible. It must accept POST requests from external IP addresses without redirects, authentication challenges, or firewall blocks
3. If the store is behind Cloudflare, configure a bypass rule for the Mollie webhook endpoints. Cloudflare's default bot protection challenges automated POST requests. See [Cloudflare Configuration](#cloudflare-configuration) below
4. If a GeoIP or store-switching module is active, exclude `/mollie/checkout/webhook/` from its redirect logic. The self-test flags known GeoIP modules when it detects them
5. If the store is in maintenance mode during a payment, the maintenance page intercepts the webhook. Use Magento's IP allowlist to allow webhook processing during maintenance windows:
   ```bash
   php bin/magento maintenance:allow-ips --none
   ```
6. Check whether **Process transactions in the queue** is enabled under **Stores → Configuration → Mollie → Developer Settings → Advanced**. If it is, and the `mollie.transaction.processor` consumer is not running, webhooks are accepted but orders are not updated. Start the consumer:
   ```bash
   php bin/magento queue:consumers:start mollie.transaction.processor
   ```

### Orders stuck in pending payment

Orders remain in `pending_payment` when the webhook was not received or could not be processed.

1. Check the webhook troubleshooting steps above first
2. Enable the pending orders cron job to automatically recover stuck orders:
   - Go to **Stores → Configuration → Mollie → Order Management**
   - Set **Enable Pending Orders Cron Job** to **Yes**
   - Click **Save Config**

   The cron job checks all Mollie orders that have been in `pending_payment` for between 30 minutes and 10 days, queries the Mollie API for their current status, and updates them accordingly. When an order is updated through this cron job, a comment is added to the order history noting that the webhook was not received.

3. To recover a single stuck order immediately, you can force a status check manually. Open the order in Magento Admin, and from the order view Mollie will re-process the transaction on the next webhook or you can trigger it via the Mollie Dashboard by resending the webhook notification from the payment detail page.

4. Enable debug logging (see above) and check `var/log/mollie.log` for errors that occurred when the webhook was received. Processing errors are logged with the order ID, which makes it straightforward to correlate a stuck order with a log entry.

### API key errors

API key errors appear when the key is missing, has the wrong format, or does not match the selected mode.

- A test key starts with `test_`. A live key starts with `live_`. Using a live key in test mode or vice versa causes an authentication error
- The extension validates the key format before saving. If the **Profile ID** field under the key fields is empty after saving, the key was rejected by the Mollie API
- Keys are stored encrypted. If the Magento encryption key changes (for example, after a migration), stored keys may become unreadable. Re-enter the keys manually
- In a multi-store installation, keys may be set at a different scope than you expect. Use the store view switcher at the top of the configuration page to check the key at each scope — see [API Keys](API_KEYS.md)
- Click **Test Apikey** (the button next to the key fields) to validate the currently active key against the Mollie API without leaving the configuration page

### Cache-related issues

Several types of problems resolve after clearing the cache:

- Payment methods added or removed in Magento Admin are not visible at checkout
- Configuration changes (mode, API key, enabled/disabled state) do not take effect
- Payment method icons do not update

Flush the cache after any configuration change:

```bash
php bin/magento cache:flush
```

Or via Magento Admin: go to **System → Cache Management** and click **Flush Magento Cache**.

If problems persist after flushing, regenerate static content (required when switching Magento modes or after deploying changes to a production server):

```bash
php bin/magento setup:static-content:deploy
```

### Mollie Components not loading

Mollie Components renders the credit card entry form inline on the checkout page. If the embedded form does not appear and customers are redirected to Mollie's hosted payment page instead, check the following:

1. Confirm **Use Mollie Components** is set to **Yes** under **Stores → Configuration → Mollie → Payment Methods → Credit Card**
2. Confirm the **Profile ID** field under **Stores → Configuration → Mollie → General → Mollie Configuration** is populated. Components requires the Profile ID to initialise. The Profile ID is set automatically when you save a valid API key — if it is empty, re-save your API key
3. Flush the cache and reload the checkout page in a fresh browser session (or with the browser cache cleared) to ensure the updated configuration is served
4. Open the browser developer console on the checkout page and check for JavaScript errors. A Content Security Policy (CSP) that blocks requests to `js.mollie.com` prevents Components from loading. Add `js.mollie.com` to your CSP `script-src` directive if you manage CSP headers manually

## Cloudflare Configuration

Cloudflare's WAF Managed Rules, Bot Fight Mode, Super Bot Fight Mode, and custom firewall rules can block incoming webhook requests from Mollie, leaving orders stuck in pending status. Apply the following configuration to allow webhook traffic through.

### 1. Retrieve Mollie's outgoing IP addresses

Mollie publishes its current outgoing IP ranges. Retrieve them before configuring access rules:

```bash
curl https://api.mollie.com/v2/outgoing-ips
```

Save the returned list — you will need it in steps 3 and 4.

### 2. Create a WAF custom rule to allow webhook paths

In your Cloudflare dashboard, go to **Security → WAF → Custom Rules** and create a rule that bypasses WAF checks for Mollie's webhook endpoints.

Match expression (adjust your zone as needed):

```
(http.request.uri.path contains "/mollie/checkout/webhook") or
(http.request.uri.path contains "/mollie/express/webhook") or
(http.request.uri.path contains "/mollie_subscriptions/api/webhook")
```

Set the action to **Skip** and select **All remaining custom rules** and **WAF Managed Rules**.

### 3. Disable Bot Fight Mode for webhook paths

Bot Fight Mode blocks requests it identifies as automated, which includes Mollie's webhook delivery. You have three options:

- **Global disable** (not recommended for production): turn off Bot Fight Mode entirely under **Security → Bots**
- **Bot Management skip rule**: create a WAF custom rule that matches the webhook paths (same expression as step 2) and sets the action to **Skip → Bot Fight Mode**
- **Cloudflare Workers**: deploy a Worker on the webhook paths that passes requests through without Bot Fight Mode interference

### 4. Verify IP Access Rules

Confirm that none of Mollie's outgoing IPs (from step 1) are blocked under **Security → WAF → Tools → IP Access Rules**. Remove any block rules that overlap with Mollie's IP ranges.

### 5. Check Page Rules and Configuration Rules

Page Rules and Configuration Rules can override security settings. Review rules that match the webhook paths and confirm none of them re-enable bot protection or trigger a challenge.

### Verification

After applying the configuration, use the self-test (see [Built-in Self-Test](#built-in-self-test)) to confirm the webhook endpoint is reachable. The test makes an outbound POST request to your own store, so it will fail if Cloudflare is still challenging the request.

**Common symptoms after incomplete configuration:**

| Symptom | Likely cause |
|---|---|
| Orders occasionally stuck in pending | Rate-limiting rule interfering with webhook delivery |
| HTTP 403 on webhook endpoint | WAF or firewall rule still active for that path |
| Webhook works in test mode but not live | Different Cloudflare zone or rule set applied per environment |

---

## Module Conflicts

Third-party Magento modules can interfere with the Mollie extension in ways that cause payment methods to disappear, webhooks to fail silently, or checkout to break entirely. The self-test (see [Built-in Self-Test](#built-in-self-test)) flags known conflicting modules such as GeoIP redirectors automatically. For unknown conflicts, follow the steps below.

### Signs of a module conflict

- Payment methods stop appearing after installing or updating another module
- JavaScript errors in the browser console on the checkout page
- Webhooks are received but orders are not updated
- Custom checkout steps or address validation fail when Mollie methods are selected

### Enable developer mode for better error messages

Magento suppresses most errors in production mode. Switch to developer mode on a staging copy of the store to expose the full exception trace:

```bash
php bin/magento deploy:mode:set developer
```

Reproduce the problem and check the error output in the browser or in the logs.

### Check the logs

Two log files capture most conflicts:

```bash
tail -f var/log/exception.log
tail -f var/log/system.log
```

Look for exceptions that coincide with the problem. The class name in the trace usually identifies the conflicting module.

### Isolate the conflict

Disable third-party modules one at a time to identify the one causing the conflict:

```bash
php bin/magento module:disable Vendor_ModuleName
php bin/magento cache:flush
```

Test after each disable. Re-enable the module once identified:

```bash
php bin/magento module:enable Vendor_ModuleName
php bin/magento cache:flush
```

### Common conflict sources

| Module type | How it conflicts |
|---|---|
| GeoIP / store switchers | Redirect the webhook URL to the wrong store view or locale |
| Custom checkout modules | Override Magento's payment step in a way that drops Mollie's additional data |
| Session / cookie modules | Interfere with cart restoration after a failed payment |
| CSP / security headers modules | Block `js.mollie.com` from loading, breaking Mollie Components |
| Custom order observers | Run after Mollie's observer and reset the order status or invoice state |

Once you have identified the conflicting module, report the conflict to the module vendor with the exception trace and the Mollie version number.

---

## Reporting an Issue

When reporting a bug or requesting support, collect the following information first:

- **Extension version**: visible in **Stores → Configuration → Mollie → General → Mollie Configuration** next to the Version button, or via `composer show mollie/magento2 | grep versions`
- **Magento version and edition**: visible in the Magento Admin footer, or via `php bin/magento --version`
- **PHP version**: `php -v`
- **Self-test results**: run the self-test and copy or screenshot the full output
- **Debug bundle**: click **Download debug information** (see above) for a redacted archive of logs and configuration
- **Steps to reproduce**: the exact sequence of actions that triggers the problem, including what payment method was used and whether it was in test or live mode
- **Order ID or transaction ID** (if applicable): the Magento order increment ID and/or the Mollie transaction ID from the Mollie Dashboard

File issues at [github.com/mollie/magento2/issues](https://github.com/mollie/magento2/issues). Attach the debug bundle and self-test output rather than pasting raw log content, as the bundle includes the context needed to diagnose most problems.

## Next Steps

- [Installation](INSTALLATION.md) — Installation steps and system requirements
- [API Keys](API_KEYS.md) — Locating and configuring your Mollie API keys
- [Configuration](CONFIGURATION.md) — All general settings explained
- [Best Practices](BEST_PRACTICES.md) — Recommended configuration for production
- [Credit Card Payments](CREDIT_CARD.md) — Mollie Components configuration
