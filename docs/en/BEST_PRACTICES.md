# Best Practices

This article covers recommended configuration and operational practices for running Mollie Payments in production.

## Run the Self-Test After Setup

After installing and configuring the extension, run the built-in self-test to catch common configuration problems before going live.

1. Go to **Stores → Configuration → Mollie → General**
2. Expand **Mollie Configuration** and click **Run Self-test**
3. Resolve any errors or warnings before accepting live payments

The self-test checks: PHP version, required extensions, webhook reachability, queue configuration, Apple Pay domain validation, and more.

## Webhooks

### Keep the Webhook URL Publicly Accessible

The Mollie webhook URL (`/mollie/checkout/webhook/`) must accept POST requests from Mollie's servers without redirects or authentication challenges. A webhook that redirects (HTTP 3xx) or returns an error (HTTP 4xx/5xx) causes order status updates to fail.

Check for the following on production servers:

- Firewall or WAF rules that block or rate-limit POST requests from external IPs
- Maintenance mode pages that intercept all requests
- Bot protection that challenges non-browser traffic

### Cloudflare

If the store is behind Cloudflare, configure a rule to bypass bot protection for the Mollie webhook URL and return URL. Cloudflare's default bot detection challenges automated POST requests, which will prevent webhooks from being processed. See the [Cloudflare configuration guide](https://github.com/mollie/magento2/wiki/Cloudflare-Configuration-for-Mollie-Webhooks) for the specific rules to create.

### GeoIP Modules

GeoIP modules that redirect visitors to a store based on their location can rewrite or block webhook requests, because Mollie's servers originate from IP addresses that may resolve to a different country or store. Exclude the webhook URL and the return URL from any GeoIP redirect or store-switching logic.

## Transaction Processing

### Keep Queue Processing Enabled

Queue-based transaction processing is enabled by default. When a webhook arrives, the order update is placed on the `mollie.transaction.processor` queue and handled asynchronously. This prevents webhook timeouts on stores where confirmation emails, invoices, and other post-payment work take a long time to complete.

Verify the consumer is running after setup using the self-test. If the queue is configured but the consumer is not running, webhooks will be accepted but orders will not be updated.

If running on a platform with a consumer allowlist, add `mollie.transaction.processor` to the allowed consumers.

### Enable the Pending Orders Cron Job

Enable the pending orders cron job to automatically recover orders that are stuck in pending status due to missed or failed webhooks.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Enable Pending Orders Cron Job** to **Yes**

The default batch size of 25 is appropriate for most stores. Reduce it on stores with limited cron resources.

## Order Management

### Use a Unique Status for Pending Bank Transfers

Bank transfer payments can remain pending for several days. Using the default `pending_payment` status for these orders makes it difficult to distinguish them from other unprocessed orders in the admin grid.

Create a dedicated order status (for example, `pending_banktransfer`) and assign it under **Stores → Configuration → Mollie → Order Management**.

### Enable Second Chance Emails for Abandoned Checkouts

Second chance emails send a payment link to customers who started but did not complete a payment. Enable automatic sending to recover these orders without manual intervention.

1. Go to **Stores → Configuration → Mollie → Second Chance Email**
2. Set **Enable Second Chance Email** to **Yes**
3. Set **Automatically Send Second Chance Emails** to **Yes**
4. Configure the delay and email template to match the store's communication style

Only orders that remain pending and have no completed transaction on the same email address receive the email.

## Payment Methods

### Enable the Methods API

The methods API filters the payment methods shown at checkout based on the customer's country and cart total. This prevents customers from selecting methods that are not available for their order.

1. Go to **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Set **Enable the Methods API** to **Yes**

This requires one additional API call per checkout session. On stores where API latency is a concern, disable it and configure eligible methods manually.

## Security

### Enable Encryption for Payment Details

Card metadata (card type, last four digits, etc.) is stored with each order. Enable encryption to store this data encrypted at rest.

1. Go to **Stores → Configuration → Mollie → Order Management**
2. Set **Encrypt Payment Details** to **Yes**

### Use Separate API Keys per Environment

Never use a live API key on a staging or development environment. Create a separate Mollie profile or use the test API key on all non-production stores. This prevents test orders from appearing in live reporting and avoids accidental charges.

When running multiple websites in a single Magento installation, configure API keys at the website scope so each site uses the correct profile and mode.

## Next Steps

- [Configuration](CONFIGURATION.md) — All general settings explained
- [Troubleshooting](TROUBLESHOOTING.md) — Common problems and how to resolve them
