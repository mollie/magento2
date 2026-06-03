# Second Chance Email

This article explains how to configure and use the Second Chance Email feature of Mollie Payments for Magento 2, which sends a payment reminder to customers who started but did not complete a payment.

## Enable Second Chance Email

Enabling the feature adds a **Send Payment Reminder** button to eligible orders in Magento Admin and unlocks the configuration options below. No emails are sent automatically until you also enable automated delivery.

1. Go to **Stores → Configuration → Mollie → Second Chance Email**
2. Set **Enable Second Chance Email** to **Yes**
3. Click **Save Config** and flush the cache

The **Send Payment Reminder** button appears on the order detail page for orders in the `new`, `pending_payment`, or `canceled` state. Clicking it sends the email immediately and records the send in **Sales → Mollie Payment Reminders → Sent**.

## Automate Delivery

When automated delivery is on, the extension queues an order for a reminder the moment the customer is redirected to Mollie. A cron job checks the queue every five minutes and sends the email once the configured delay has passed.

1. Go to **Stores → Configuration → Mollie → Second Chance Email**
2. Set **Automatically Send Second Chance Emails** to **Yes**
3. Set **Second Chance Email Delay** to the number of hours to wait before sending (1–8 hours, default: **1**)
4. Click **Save Config** and flush the cache

Before sending each queued reminder, the extension checks two conditions. If either fails, the pending record is removed without sending:

- The order is not already in `processing` or `complete` state.
- All items on the order are still saleable (in stock).

Bank transfer orders are excluded from the queue entirely, as customers are already directed to complete the payment through their bank.

The extension sends at most one reminder per order. Once a reminder is sent, the customer's other queued reminders are removed. Sent reminder records older than one week are cleaned up automatically.

## Payment Link Behaviour

Every reminder email contains a unique payment link. What happens when the customer clicks it depends on the state of the original order:

- **Order still pending**: the customer is redirected to the original Mollie checkout URL to complete the same payment.
- **Order cancelled**: a new order is created from the original order's items and the customer is redirected to a fresh Mollie checkout.

The link includes UTM parameters for analytics tracking: `utm_source=second_chance_email`, `utm_medium=mollie_second_chance`, `utm_campaign=second_chance_order`.

### Payment Method for Reorders

When the original order is cancelled and a new order must be created, configure which payment method the new order uses.

1. Go to **Stores → Configuration → Mollie → Second Chance Email**
2. Set **Payment Method To Use For Second Chance Payments** to one of:
   - **Use the method of the original order** (default): the new order uses the same Mollie payment method the customer originally selected
   - Any enabled Mollie payment method: forces all reorders to that method
3. Click **Save Config** and flush the cache

## Customise the Email Template

The default template includes the order items list, a payment button, and a brief message. Customise it through the standard Magento email template editor.

1. Go to **Marketing → Communications → Email Templates**
2. Click **Add New Template**
3. Under **Load default template**, select **Mollie Second Chance Email** and click **Load Template**
4. Edit the template as needed
5. Click **Save Template**
6. Go to **Stores → Configuration → Mollie → Second Chance Email**
7. Set **Second Chance Email Template** to your new template
8. Click **Save Config** and flush the cache

The following variables are available in the template:

| Variable | Description |
|---|---|
| `{{var link}}` | The unique payment URL |
| `{{var customer.name}}` | Customer's full name |
| `{{var customer.email}}` | Customer's email address |
| `{{var order.increment_id}}` | Order number |
| `{{var order.total}}` | Order total |
| `{{var store.frontend_name}}` | Store name |

The subject line is set with the `@subject` directive at the top of the template: `{{trans "Complete your payment from %store_name" store_name=$store.frontend_name}}`.

## BCC

To receive a blind carbon copy of every second chance email sent, enter one or more email addresses in the BCC field.

1. Go to **Stores → Configuration → Mollie → Second Chance Email**
2. Enter a comma-separated list of email addresses in **Send BCC to** (leave empty to disable)
3. Click **Save Config** and flush the cache

## Monitor and Manage Reminders

The reminder queue is accessible under **Sales → Mollie Payment Reminders**.

- **Pending**: orders waiting to receive a reminder. Shows the order ID and when the record was created. Use this to identify orders that will receive a reminder soon, or to manually trigger or delete individual items.
- **Sent**: orders for which a reminder has already been sent. Use this to audit delivery or remove stale records.

Both grids support mass actions to send or delete multiple records at once.

## Next Steps

- [Order Management](ORDER_MANAGEMENT.md): order statuses, failed payment handling, and pending order recovery
- [Configuration](CONFIGURATION.md): all general settings
- [Payment Methods](PAYMENT_METHODS.md): enabling and configuring individual payment methods
- [Best Practices](BEST_PRACTICES.md): recommended production settings
