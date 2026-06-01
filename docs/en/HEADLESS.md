# Headless Integration

This article is for developers building headless or PWA storefronts on top of Magento 2 with Mollie Payments for Magento 2. It covers both the GraphQL and REST integration paths.

GraphQL is the recommended approach for new headless implementations. It exposes Mollie-specific mutations and queries as first-class schema extensions. The REST API is the alternative for frontends that already use Magento's REST endpoints or that need access to admin-restricted endpoints.

## Prerequisites

- Mollie Payments for Magento 2 is installed and enabled — see [Installation](INSTALLATION.md)
- A valid API key is configured in Magento Admin — see [API Keys](API_KEYS.md)
- The Magento GraphQL endpoint (`/graphql`) or REST base URL (`/rest/`) is reachable from your frontend application

---

## GraphQL

### What the Extension Adds to GraphQL

Mollie Payments for Magento 2 extends Magento's standard GraphQL schema with:

- Additional fields on `AvailablePaymentMethod`, `PaymentMethod`, `SelectedPaymentMethod`, `Cart`, `CartPrices`, `Order`, and `StoreConfig`
- Additional input fields on `PaymentMethodInput` and `PlaceOrderInput`
- Two root queries: `mollieCustomerOrder` and `molliePaymentMethods`
- Four root mutations: `mollieProcessTransaction`, `mollieRestoreCart`, `mollieApplePayValidation`, and `molliePaymentLinkRedirect`
- One authenticated customer query field: `mollie_saved_cards`
- One authenticated customer mutation: `revokeMollieSavedCard`

### Checkout Flow Overview

A headless Mollie checkout follows these steps, each mapped to a standard Magento or Mollie-specific GraphQL call:

1. Create or retrieve a guest cart (`createEmptyCart`)
2. Add products and addresses using standard Magento mutations
3. Set a shipping method (`setShippingMethodsOnCart`)
4. Fetch available payment methods, including Mollie-specific metadata (`cart.available_payment_methods`)
5. Set the payment method, passing any Mollie-specific input (`setPaymentMethodOnCart`)
6. Place the order, capturing `mollie_redirect_url` and `mollie_payment_token` from the response (`placeOrder`)
7. Redirect the customer to `mollie_redirect_url`
8. On return, call `mollieProcessTransaction` with the payment token to confirm the outcome
9. Use `redirect_to_success_page` or `redirect_to_cart` to route the customer appropriately

---

### Reading Mollie Store Config

Read the active profile ID and whether live mode is enabled without requiring authentication.

```graphql
query {
  storeConfig {
    mollie {
      profile_id
      live_mode
    }
  }
}
```

**Response fields:**

| Field | Type | Description |
|---|---|---|
| `profile_id` | `String` | The Mollie profile ID configured in Magento Admin |
| `live_mode` | `Boolean` | `true` when Mollie is in live mode, `false` for test mode |

---

### Fetching Available Payment Methods

The `molliePaymentMethods` query returns all methods activated on your Mollie account and enabled in Magento, sorted alphabetically. Use this for a method picker outside the cart context, for example on a landing page.

```graphql
query {
  molliePaymentMethods(input: { amount: 49.99, currency: "EUR" }) {
    methods {
      code
      name
      image
    }
  }
}
```

**Input fields (`MolliePaymentMethodsInput`):**

| Field | Type | Default | Description |
|---|---|---|---|
| `amount` | `Float` | `10` | Order amount used to filter methods that have minimum or maximum limits |
| `currency` | `String` | `EUR` | ISO 4217 currency code; omit to retrieve all enabled methods without amount filtering |

When `currency` is omitted the query retrieves all activated methods regardless of amount. When `currency` is provided, the Mollie API filters out methods unavailable for that amount and currency combination.

The response is cached by Magento's GraphQL resolver cache under the `mollie_payment_methods` cache tag. Flush the Magento cache after changing method configuration.

---

### Payment Method Metadata on the Cart

When you retrieve `available_payment_methods` from a cart, Mollie extends each entry with additional fields.

```graphql
query getPaymentMethods($cartId: String!) {
  cart(cart_id: $cartId) {
    available_payment_methods {
      code
      title
      mollie_meta {
        image
      }
      mollie_available_issuers {
        name
        code
        image
        svg
      }
      mollie_available_terminals {
        id
        brand
        model
        serialNumber
        description
      }
    }
  }
}
```

**`mollie_meta`** returns the URL of the payment method's SVG icon served from Magento's static asset directory.

**`mollie_available_issuers`** returns a list of issuers for methods that require an issuer selection, such as iDEAL or KBC. The list is empty for methods that have no issuers.

**`mollie_available_terminals`** returns the Point of Sale terminals registered on your Mollie account. This field is only populated when the method code is `mollie_methods_pointofsale`. For all other methods it returns an empty array.

---

### Setting the Payment Method

Pass Mollie-specific input alongside the standard payment method code.

```graphql
mutation setPaymentMethodOnCart(
  $cartId: String!
  $method: String!
  $issuer: String
  $terminal: String
  $cardToken: String
  $applePayToken: String
) {
  setPaymentMethodOnCart(input: {
    cart_id: $cartId
    payment_method: {
      code: $method
      mollie_selected_issuer: $issuer
      mollie_selected_terminal: $terminal
      mollie_card_token: $cardToken
      mollie_applepay_payment_token: $applePayToken
    }
  }) {
    cart {
      selected_payment_method {
        code
      }
    }
  }
}
```

**Mollie input fields on `PaymentMethodInput`:**

| Field | Type | When to use |
|---|---|---|
| `mollie_selected_issuer` | `String` | The issuer `code` from `mollie_available_issuers`, required for iDEAL and other issuer-based methods |
| `mollie_selected_terminal` | `String` | The terminal `id` from `mollie_available_terminals`, required for Point of Sale payments |
| `mollie_card_token` | `String` | The card token generated by Mollie Components (Credit Card only) |
| `mollie_applepay_payment_token` | `String` | The payment token from the Apple Pay JS session |

All four fields are optional — supply only the ones relevant to the chosen payment method.

---

### Placing the Order

Extend the standard `placeOrder` mutation to pass a custom return URL and to read the Mollie redirect URL and payment token back.

```graphql
mutation placeOrder($cartId: String!) {
  placeOrder(input: {
    cart_id: $cartId
    mollie_return_url: "https://example.com/checkout/mollie/return"
  }) {
    order {
      order_id
      mollie_redirect_url
      mollie_payment_token
    }
  }
}
```

**`mollie_return_url`** (optional, on `PlaceOrderInput`): the URL Mollie redirects the customer to after they complete or cancel payment. When omitted, the extension uses the store's default return URL configured in Magento Admin.

**`mollie_redirect_url`** (on `Order`): the Mollie-hosted payment page URL. Redirect the customer to this URL to complete payment.

**`mollie_payment_token`** (on `Order`): a token that uniquely identifies this payment. Store this token so you can call `mollieProcessTransaction` when the customer returns.

---

### Processing the Transaction on Return

After the customer returns from Mollie's payment page, call `mollieProcessTransaction` to sync the payment status to Magento.

```graphql
mutation processTransaction($paymentToken: String!) {
  mollieProcessTransaction(input: {
    payment_token: $paymentToken
  }) {
    paymentStatus
    redirect_to_success_page
    redirect_to_cart
    cart {
      id
    }
  }
}
```

**Input:** `payment_token` is the value returned by `placeOrder` in the `mollie_payment_token` field, or the `payment_token` query parameter appended to the return URL by the extension.

**Response fields:**

| Field | Type | Description |
|---|---|---|
| `paymentStatus` | `PaymentStatusEnum` | The Mollie payment status at the time of the call |
| `redirect_to_success_page` | `Boolean` | `true` when the payment succeeded and the customer should see the order confirmation |
| `redirect_to_cart` | `Boolean` | `true` when the payment failed, was cancelled, or expired |
| `cart` | `Cart` | The restored cart, present only when `redirect_to_cart` is `true` |

**`PaymentStatusEnum` values:** `CREATED`, `OPEN`, `PENDING`, `AUTHORIZED`, `PAID`, `SHIPPING`, `COMPLETED`, `CANCELED`, `EXPIRED`, `REFUNDED`, `FAILED`, `ERROR`

When `redirect_to_cart` is `true`, the extension reactivates the cart automatically and returns it in the `cart` field so the customer can amend their order without starting over.

---

### Restoring a Cart Manually

If you need to reactivate a cart independently of `mollieProcessTransaction`, use `mollieRestoreCart`.

```graphql
mutation restoreCart($cartId: String!) {
  mollieRestoreCart(input: {
    cart_id: $cartId
  }) {
    cart {
      id
      total_quantity
    }
  }
}
```

This mutation accepts the masked cart ID (the same `cart_id` string used by all other cart mutations) and marks the cart as active. It validates that the authenticated customer owns the cart. Guest carts can be restored without authentication.

---

### Looking Up an Order by Hash

`mollieCustomerOrder` retrieves a full order by the encrypted hash appended to the return URL. Use this to display order details on a headless success page without requiring the customer to be logged in.

```graphql
query getOrderByHash($hash: String!) {
  mollieCustomerOrder(hash: $hash) {
    id
    increment_id
    status
    grand_total {
      value
      currency
    }
    items {
      product_name
      quantity_ordered
    }
  }
}
```

The `hash` parameter comes from the `order_id` query parameter appended to the return URL by the extension. The resolver decrypts the hash internally and returns a standard `CustomerOrder` object.

---

### Payment Link Redirect

When a customer opens a payment link (sent via the Second Chance Email or generated manually), call `molliePaymentLinkRedirect` to determine what action to take.

```graphql
mutation handlePaymentLink($order: String!) {
  molliePaymentLinkRedirect(order: $order) {
    redirect_url
    already_paid
    is_expired
  }
}
```

The `order` argument is the encrypted order ID included in the payment link URL. The response tells you whether to:

- Redirect the customer to `redirect_url` to complete payment
- Show an "already paid" message when `already_paid` is `true`
- Show an "expired" message when `is_expired` is `true`

---

### Saved Cards (Credit Card)

Saved cards are available when the Customers API is enabled for Credit Card payments in Magento Admin. All saved card operations require an authenticated customer token in the `Authorization` request header.

#### List Saved Cards

```graphql
query {
  customer {
    mollie_saved_cards {
      mandate_id
      card_label
      card_number_last4
      card_expiry_date
      card_holder
    }
  }
}
```

**Fields on `MollieSavedCard`:**

| Field | Type | Description |
|---|---|---|
| `mandate_id` | `String!` | The Mollie mandate ID, used to revoke the card |
| `card_label` | `String!` | Card brand, for example `Visa` or `Mastercard` |
| `card_number_last4` | `String!` | Last four digits of the card number |
| `card_expiry_date` | `String` | Expiry date in `MM/YYYY` format |
| `card_holder` | `String` | Name of the cardholder as stored in Mollie |

#### Revoke a Saved Card

```graphql
mutation revokeCard($mandateId: String!) {
  revokeMollieSavedCard(mandate_id: $mandateId) {
    success
  }
}
```

Revoking a card revokes the underlying Mollie mandate. The card can no longer be used for future payments. On success the resolver returns `{ success: true }`.

---

### Apple Pay Validation

When implementing a custom Apple Pay button in a headless storefront, you must validate the Apple Pay merchant session server-side before displaying the payment sheet. Use `mollieApplePayValidation` to proxy this call through the Mollie API.

```graphql
mutation validateApplePay($validationUrl: String!, $domain: String) {
  mollieApplePayValidation(
    validationUrl: $validationUrl
    domain: $domain
  ) {
    response
  }
}
```

**Arguments:**

| Argument | Type | Description |
|---|---|---|
| `validationUrl` | `String!` | The validation URL provided by the Apple Pay JS `onvalidatemerchant` event |
| `domain` | `String` | The domain to register; defaults to the store's base URL when omitted |

The `response` field contains the raw JSON string returned by Apple's servers. Pass it directly to `session.completeMerchantValidation()` in your Apple Pay JS session handler.

After validation, set the payment method using `mollie_applepay_payment_token` with the token from the Apple Pay JS `onpaymentauthorized` event.

---

### Cart Prices: Payment Fee

When a payment fee (surcharge) is configured for the selected method, it appears in the cart totals.

```graphql
query getCartWithFee($cartId: String!) {
  cart(cart_id: $cartId) {
    prices {
      mollie_payment_fee {
        fee {
          value
          currency
        }
        fee_tax {
          value
          currency
        }
      }
    }
  }
}
```

`mollie_payment_fee` is `null` when no fee is configured for the selected payment method. For configuration details, see [Payment Fee](PAYMENT_FEE.md).

---

### Mollie Components in a Headless Context

Mollie Components is the embedded card entry form for Credit Card payments. In a headless storefront, you initialise the Mollie JS library client-side using the profile ID from `storeConfig`.

1. Query `storeConfig { mollie { profile_id live_mode } }` on page load.
2. Initialise the Mollie JS library: `Mollie(profileId, { testmode: !live_mode })`.
3. Create and mount component fields (`cardNumber`, `cardHolder`, `expiryDate`, `verificationCode`) using `mollie.createComponent()`.
4. On form submit, call `mollie.createToken()` to generate a `mollie_card_token`.
5. Pass `mollie_card_token` in the `setPaymentMethodOnCart` mutation.
6. Proceed with `placeOrder` as normal — the token is attached to the payment and no redirect to a hosted page is needed for valid tokenised payments.

**Important:** Mollie Components requires a valid profile ID. If `storeConfig.mollie.profile_id` is `null`, fall back to the standard redirect flow.

---

### GraphQL Configuration Required in Magento Admin

No additional configuration is needed to enable the GraphQL endpoints. The schema extensions are active as soon as the module is enabled and a valid API key is configured.

To use specific features:

- **Saved cards:** enable **Save Cards** under **Stores → Configuration → Mollie → Payment Methods → Credit Card**
- **Apple Pay:** enable Apple Pay under **Stores → Configuration → Mollie → Payment Methods → Apple Pay** and ensure your domain is verified in the Mollie Dashboard
- **Point of Sale terminals:** enable Point of Sale under **Stores → Configuration → Mollie → Payment Methods → Point Of Sale (POS)** and register your terminals in the Mollie Dashboard
- **Payment fee:** configure a surcharge per method — see [Payment Fee](PAYMENT_FEE.md)

---

### GraphQL Error Handling

GraphQL errors from Mollie resolvers follow Magento's standard error format, with the error message in the `errors` array of the response.

Common errors and causes:

| Error | Cause |
|---|---|
| `Missing "payment_token" input argument` | `mollieProcessTransaction` called without a `payment_token` in the input |
| `No order found with token "..."` | The payment token does not match any order; this can happen if the token was already consumed or the order was cancelled |
| `Order not found` | `molliePaymentLinkRedirect` received an invalid or expired encrypted order ID |
| `The current customer is not authorized.` | A saved cards query or revocation was called without a valid customer token in the `Authorization` header |
| `Saved cards are not enabled.` | `revokeMollieSavedCard` was called but the Customers API is not enabled for Credit Card in Magento Admin |
| `Required parameter "cart_id" is missing` | `mollieRestoreCart` called without a `cart_id` in the input |

When `mollieProcessTransaction` returns `FAILED`, `CANCELED`, or `EXPIRED`, `redirect_to_cart` is `true` and the cart is automatically restored. Do not call `mollieRestoreCart` separately in this case.

---

## REST API

### REST Checkout Flow Overview

The REST integration uses Magento's standard cart and order endpoints together with Mollie-specific endpoints for payment orchestration.

1. Build the cart using standard Magento REST (items, addresses, shipping)
2. Fetch issuer and terminal metadata from `GET /rest/V1/mollie/payment-method/meta`
3. Set the payment method using standard Magento REST, passing Mollie-specific fields in `additional_data`
4. Generate a payment token from the active cart
5. Place the order using standard Magento REST
6. Start the Mollie transaction with the payment token — returns the Mollie checkout URL
7. Redirect the customer to the checkout URL
8. On return, retrieve the order status by hash or payment token
9. If the payment failed or was cancelled, reset the cart

---

### Payment Method Metadata

Retrieve the issuers and terminals available per payment method before presenting the payment step.

```
GET /rest/V1/mollie/payment-method/meta
```

No authentication required. Response is an array of method objects:

```json
[
  {
    "code": "mollie_methods_ideal",
    "issuers": [
      {
        "id": "ABNANL2A",
        "name": "ABN AMRO",
        "image": "https://...",
        "images": { "size1x": "...", "size2x": "...", "svg": "..." }
      }
    ],
    "terminals": []
  },
  {
    "code": "mollie_methods_pointofsale",
    "issuers": [],
    "terminals": [
      {
        "id": "term_abc123",
        "brand": "Verifone",
        "model": "P400",
        "serialNumber": "123-456",
        "description": "Counter terminal"
      }
    ]
  }
]
```

---

### Setting the Payment Method

Use Magento's standard payment method endpoint and pass Mollie-specific values in `additional_data`.

```
PUT /rest/V1/carts/mine/selected-payment-method
```

For guest carts: `PUT /rest/V1/guest-carts/:cartId/selected-payment-method`

```json
{
  "method": {
    "method": "mollie_methods_ideal",
    "additional_data": {
      "selected_issuer": "ABNANL2A"
    }
  }
}
```

**Mollie `additional_data` fields:**

| Key | When to use |
|---|---|
| `selected_issuer` | The issuer `id` from the metadata response, required for iDEAL and other issuer-based methods |
| `selected_terminal` | The terminal `id` from the metadata response, required for Point of Sale |
| `card_token` | The token generated by Mollie Components (Credit Card only) |

---

### Generating a Payment Token

Generate a payment token from the active cart. Do this before placing the order.

For authenticated customers:

```
GET /rest/V1/carts/mine/mollie/payment-token
```

For guest carts:

```
GET /rest/V1/guest-carts/:cartId/mollie/payment-token
```

Response is a plain string token. Store it — you need it to start the transaction after the order is placed.

---

### Placing the Order

Place the order using the standard Magento endpoint. The extension automatically links the payment token you generated to the new order during order submission.

```
POST /rest/V1/carts/mine/payment-information
```

For guest carts: `POST /rest/V1/guest-carts/:cartId/payment-information`

```json
{
  "paymentMethod": {
    "method": "mollie_methods_ideal",
    "additional_data": {
      "selected_issuer": "ABNANL2A"
    }
  }
}
```

Response is the Magento order ID (integer).

---

### Starting the Mollie Transaction

After the order is placed, start the Mollie transaction with the payment token. This creates the payment at Mollie and returns the URL to redirect the customer to.

```
POST /rest/V1/mollie/transaction/start
```

```json
{
  "token": "your-payment-token"
}
```

Response is a plain string containing the Mollie checkout URL. Redirect the customer to this URL to complete payment.

---

### Processing the Return

After the customer returns from Mollie's payment page, retrieve the order to determine the payment outcome. The return URL includes an encrypted `order_id` hash and a `payment_token` parameter.

Retrieve by hash (anonymous, no authentication required):

```
GET /rest/V1/mollie/get-order/by-hash/:hash
POST /rest/V1/mollie/get-order/by-hash/:hash
```

Retrieve by payment token:

```
POST /rest/V1/mollie/get-order/by-payment-token/:token
```

Both return a Magento order object. Check the order's status to determine whether to show a success page or route the customer back to the cart.

---

### Resetting the Cart After a Failed Payment

If the payment failed or was cancelled, restore the cart so the customer can try again.

```
POST /rest/V1/mollie/reset-cart/:hash
```

The `hash` is the encrypted order ID from the return URL. No request body required. On success, the original cart is reactivated and associated with the customer's session.

---

### Payment Link Redirect

When a customer opens a payment link, determine what action to take before redirecting them.

```
GET /rest/V1/mollie/get-payment-link-redirect/:hash
```

The `hash` is the encrypted order ID from the payment link URL.

Response:

```json
{
  "redirect_url": "https://...",
  "already_paid": false,
  "is_expired": false
}
```

Redirect the customer to `redirect_url` to complete payment. Show an appropriate message when `already_paid` or `is_expired` is `true`.

---

### Saved Cards via REST

List and delete saved cards for the authenticated customer.

List saved cards (requires `Authorization` header):

```
GET /rest/V1/mollie/customer/me/saved-cards
```

Delete a saved card:

```
DELETE /rest/V1/mollie/customer/me/saved-cards/:mandateId
```

The `mandateId` comes from the list response. Deleting a card revokes the underlying Mollie mandate.

---

## Next Steps

- [Installation](INSTALLATION.md) — Installing and enabling the extension
- [API Keys](API_KEYS.md) — Configuring your Mollie API key
- [Credit Card Payments](CREDIT_CARD.md) — Mollie Components and saved cards configuration
- [Apple Pay](APPLE_PAY.md) — Apple Pay merchant validation and domain registration
- [Point of Sale](POINT_OF_SALE.md) — Terminal configuration for in-person payments
- [Payment Fee](PAYMENT_FEE.md) — Adding a surcharge to payment methods
- [Second Chance Email](SECOND_CHANCE_EMAIL.md) — Payment link generation and configuration
