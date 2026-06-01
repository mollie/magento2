# Payment Fee

This article explains how to configure a payment surcharge (payment fee) per payment method, set up tax handling, and understand how fees appear in order totals, invoices, and credit memos.

## Prerequisites

- Mollie Payments for Magento 2 is installed and at least one payment method is enabled — see [Installation](INSTALLATION.md) and [Payment Methods](PAYMENT_METHODS.md)
- The payment method you want to add a surcharge to must be active in **Stores → Configuration → Mollie → Payment Methods**

## What the Payment Fee Does

The payment fee adds a surcharge to the order total when a customer selects a specific Mollie payment method at checkout. The fee appears as a separate line item labelled **Payment Fee** in the cart, order summary, invoice, and credit memo. It is configured per payment method, so you can charge different amounts or percentages for different methods, or leave the fee disabled entirely for methods where you prefer to absorb the cost.

## Configure a Fee for a Payment Method

Each active Mollie payment method has its own surcharge settings. The steps below use iDEAL as an example, but the same fields are present for every supported method.

1. Go to **Stores → Configuration → Mollie → Payment Methods**
2. Expand the payment method you want to configure (for example, **iDEAL**)
3. Set **Payment Surcharge** to one of the following:
   - **No** — no fee is added (default)
   - **Fixed Fee** — a fixed amount is added to every order
   - **Percentage** — a percentage of the order subtotal is added
   - **Fixed Fee and Percentage** — both a fixed amount and a percentage are added together
4. Configure the fields that appear based on your chosen type (see sections below)
5. Click **Save Config** and flush the cache

The surcharge fields appear only when the payment method is enabled and the surcharge type is not **No**.

### Fixed Fee

A fixed fee adds the same amount to every qualifying order regardless of the order value.

1. After setting **Payment Surcharge** to **Fixed Fee**, enter the fee amount (including tax) in **Payment Surcharge fixed amount**
2. Select the correct tax class in **Payment Surcharge Tax Class** to ensure the fee is taxed correctly
3. Click **Save Config** and flush the cache

The fixed amount field accepts a decimal value. Use a period as the decimal separator — commas and percentage signs are stripped automatically on save.

### Percentage

A percentage fee is calculated as a fraction of the order subtotal.

1. After setting **Payment Surcharge** to **Percentage**, enter a value between `0` and `10` in **Payment Surcharge percentage** — for example, enter `1.5` for 1.5%
2. Optionally enter a value in **Payment Surcharge limit** to cap the fee at a maximum amount (including tax)
3. Select the correct tax class in **Payment Surcharge Tax Class**
4. Click **Save Config** and flush the cache

The percentage is applied to the base subtotal including tax. You can control whether shipping and discounts are also included in the base amount — see [Surcharge Calculation Basis](#surcharge-calculation-basis) below.

### Fixed Fee and Percentage

This type combines both approaches: the calculated percentage and the fixed amount are added together to produce the final fee.

1. After setting **Payment Surcharge** to **Fixed Fee and Percentage**, enter values in both **Payment Surcharge fixed amount** and **Payment Surcharge percentage**
2. Optionally enter a value in **Payment Surcharge limit** to cap the combined fee at a maximum amount (including tax)
3. Select the correct tax class in **Payment Surcharge Tax Class**
4. Click **Save Config** and flush the cache

When a limit is set, the combined fee is capped at that amount. The limit does not apply to fixed-fee-only configurations.

## Surcharge Calculation Basis

By default, the percentage surcharge is calculated on the order subtotal including tax. Two global settings in the **Invoicing & Surcharges** section let you adjust what is included in that base amount.

1. Go to **Stores → Configuration → Mollie → Order Management → Invoicing & Surcharges**
2. Set **Include shipping in Surcharge calculation**:
   - **No** (default) — the surcharge base is the subtotal only
   - **Yes** — shipping is added to the subtotal before calculating the percentage
3. Set **Include discount in Surcharge calculation**:
   - **No** (default) — discounts do not reduce the surcharge base
   - **Yes** — the discount amount is subtracted from the subtotal before calculating the percentage
4. Click **Save Config** and flush the cache

These settings apply globally to all payment methods configured with a percentage or combined surcharge. Fixed fees are not affected.

## Tax Handling

The extension applies tax to the payment fee using Magento's standard tax calculation engine. You assign a product tax class to each surcharge using the **Payment Surcharge Tax Class** field. The extension then looks up the applicable tax rate from your Magento tax rules based on the customer's address and the selected tax class.

The fee amounts you enter in the Admin are treated as tax-inclusive. For example, if you enter a fixed fee of `1.21` with a 21% tax class, the fee shown to the customer is `1.21` total: `1.00` net plus `0.21` tax. The tax component is separated and reported correctly on invoices and in Magento's tax reports.

The tax amount on the payment fee is included in the order's total tax line alongside product taxes.

## Adjusting the Total Sort Order

The **Payment Fee** line appears in order totals at sort position `25` by default, which places it after shipping and before the grand total. To change this:

1. Go to **Stores → Configuration → Sales → Sales → Checkout Totals Sort Order**
2. Change the value of **Mollie Payment Fee** to a different sort position
3. Click **Save Config** and flush the cache

Lower numbers place the line higher in the totals block. The default of `25` puts the fee between shipping (`15`) and tax (`20` by default in most installations).

## How Fees Appear in Orders, Invoices, and Credit Memos

Once an order is placed, the payment fee is stored against the order and propagated automatically.

**Order view:** The **Payment Fee** line appears in the order totals block in Magento Admin under **Sales → Orders → [order]**. It shows the combined fee and tax amount.

**Invoices:** When an invoice is created for the order, the payment fee is copied to the invoice totals automatically. It appears as a separate **Payment Fee** line on both the Admin invoice view and the PDF invoice sent to the customer.

**Credit memos:** The payment fee is refunded on the first credit memo that covers all remaining items (a full refund or the final partial refund). Partial credit memos for a subset of items do not include the payment fee. When the fee is refunded, it appears as a **Payment Fee** line in the credit memo totals.

## Limitations and Edge Cases

- The payment fee applies only to Mollie payment methods. It is not applied to any other payment gateway.
- The **Express Components** method does not support a surcharge configuration.
- The percentage surcharge is capped at a maximum of 10% by the Admin input validation. If you need a higher value, this requires a code-level change.
- When a customer changes their payment method at checkout, the fee is recalculated immediately. If the new method has no fee configured, the line disappears from the cart totals.
- The fee is stored in both the store currency and the base currency. Currency conversion uses the exchange rate active at the time the fee is collected, not at the time of invoicing.
- Partial credit memos do not refund the payment fee. Only the final credit memo that brings the refunded item quantity to zero includes the fee refund. Issue the final partial credit memo or a full credit memo to trigger the fee refund.

## Next Steps

- [Payment Methods](PAYMENT_METHODS.md) — Enabling and configuring individual payment methods
- [Order Management](ORDER_MANAGEMENT.md) — Invoice creation, refunds, and credit memos
- [Configuration](CONFIGURATION.md) — All general settings
- [Troubleshooting](TROUBLESHOOTING.md) — Common issues
