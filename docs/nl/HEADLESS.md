# Headless Integratie

Dit artikel is bedoeld voor ontwikkelaars die headless of PWA-storefronts bouwen op Magento 2 met Mollie Payments for Magento 2. Het behandelt zowel de GraphQL- als de REST-integratiepaden.

GraphQL is de aanbevolen aanpak voor nieuwe headless implementaties. Het biedt Mollie-specifieke mutations en queries als eerste-klas schema-uitbreidingen. De REST API is het alternatief voor frontends die al Magento's REST-endpoints gebruiken of toegang nodig hebben tot door admin beperkte endpoints.

## Vereisten

- Mollie Payments for Magento 2 is geinstalleerd en ingeschakeld - zie [Installation](INSTALLATION.md)
- Een geldige API-sleutel is geconfigureerd in Magento Admin - zie [API Keys](API_KEYS.md)
- Het Magento GraphQL-endpoint (`/graphql`) of de REST-basis-URL (`/rest/`) is bereikbaar vanuit je frontendapplicatie

---

## GraphQL

### Wat de extensie toevoegt aan GraphQL

Mollie Payments for Magento 2 breidt het standaard GraphQL-schema van Magento uit met:

- Extra velden op `AvailablePaymentMethod`, `PaymentMethod`, `SelectedPaymentMethod`, `Cart`, `CartPrices`, `Order` en `StoreConfig`
- Extra invoervelden op `PaymentMethodInput` en `PlaceOrderInput`
- Twee root queries: `mollieCustomerOrder` en `molliePaymentMethods`
- Vier root mutations: `mollieProcessTransaction`, `mollieRestoreCart`, `mollieApplePayValidation` en `molliePaymentLinkRedirect`
- Een geauthenticeerd klant-queryveld: `mollie_saved_cards`
- Een geauthenticeerde klant-mutation: `revokeMollieSavedCard`

### Overzicht van de checkout-stroom

Een headless Mollie-checkout volgt deze stappen, elk gekoppeld aan een standaard Magento- of Mollie-specifieke GraphQL-aanroep:

1. Maak een gastwinkelwagen aan of haal deze op (`createEmptyCart`)
2. Voeg producten en adressen toe via standaard Magento-mutations
3. Stel een verzendmethode in (`setShippingMethodsOnCart`)
4. Haal beschikbare betaalmethoden op, inclusief Mollie-specifieke metadata (`cart.available_payment_methods`)
5. Stel de betaalmethode in en geef eventuele Mollie-specifieke invoer mee (`setPaymentMethodOnCart`)
6. Plaats de bestelling en leg `mollie_redirect_url` en `mollie_payment_token` vast uit de respons (`placeOrder`)
7. Stuur de klant door naar `mollie_redirect_url`
8. Roep bij terugkeer `mollieProcessTransaction` aan met het payment token om de uitkomst te bevestigen
9. Gebruik `redirect_to_success_page` of `redirect_to_cart` om de klant naar de juiste pagina te sturen

---

### Mollie Store Config uitlezen

Lees de actieve profiel-ID en of de live-modus is ingeschakeld zonder authenticatie te vereisen.

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

**Responsvelden:**

| Veld | Type | Beschrijving |
|---|---|---|
| `profile_id` | `String` | De Mollie-profiel-ID geconfigureerd in Magento Admin |
| `live_mode` | `Boolean` | `true` wanneer Mollie in live-modus staat, `false` voor testmodus |

---

### Beschikbare betaalmethoden ophalen

De query `molliePaymentMethods` retourneert alle methoden die actief zijn op je Mollie-account en ingeschakeld zijn in Magento, gesorteerd op alfabetische volgorde. Gebruik dit voor een methodekiezer buiten de winkelwagencontext, bijvoorbeeld op een landingspagina.

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

**Invoervelden (`MolliePaymentMethodsInput`):**

| Veld | Type | Standaard | Beschrijving |
|---|---|---|---|
| `amount` | `Float` | `10` | Orderbedrag gebruikt om methoden met minimum- of maximumlimieten te filteren |
| `currency` | `String` | `EUR` | ISO 4217-valutacode; laat weg om alle ingeschakelde methoden op te halen zonder bedragfiltering |

Als `currency` wordt weggelaten, haalt de query alle geactiveerde methoden op ongeacht het bedrag. Als `currency` wordt opgegeven, filtert de Mollie API methoden eruit die niet beschikbaar zijn voor die bedrag- en valutacombinatie.

De respons wordt gecached door de GraphQL-resolver-cache van Magento onder de cachetag `mollie_payment_methods`. Leeg de Magento-cache na het wijzigen van de methodeconfiguratie.

---

### Betaalmethode-metadata op de winkelwagen

Wanneer je `available_payment_methods` ophaalt van een winkelwagen, breidt Mollie elk item uit met extra velden.

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

**`mollie_meta`** retourneert de URL van het SVG-icoon van de betaalmethode, geserveerd vanuit de statische assets-map van Magento.

**`mollie_available_issuers`** retourneert een lijst van uitgevers voor methoden die een uitgeverkeuze vereisen, zoals iDEAL of KBC. De lijst is leeg voor methoden zonder uitgevers.

**`mollie_available_terminals`** retourneert de Point of Sale-terminals die zijn geregistreerd op je Mollie-account. Dit veld is alleen gevuld wanneer de methodecode `mollie_methods_pointofsale` is. Voor alle andere methoden wordt een lege array geretourneerd.

---

### De betaalmethode instellen

Geef Mollie-specifieke invoer mee naast de standaard betaalmethodecode.

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

**Mollie-invoervelden op `PaymentMethodInput`:**

| Veld | Type | Wanneer te gebruiken |
|---|---|---|
| `mollie_selected_issuer` | `String` | De `code` van de uitgever uit `mollie_available_issuers`, vereist voor iDEAL en andere op uitgevers gebaseerde methoden |
| `mollie_selected_terminal` | `String` | Het terminal-`id` uit `mollie_available_terminals`, vereist voor Point of Sale-betalingen |
| `mollie_card_token` | `String` | Het card token gegenereerd door Mollie Components (alleen Credit Card) |
| `mollie_applepay_payment_token` | `String` | Het payment token uit de Apple Pay JS-sessie |

Alle vier velden zijn optioneel - geef alleen de velden mee die relevant zijn voor de gekozen betaalmethode.

---

### De bestelling plaatsen

Breid de standaard `placeOrder`-mutation uit om een aangepaste retour-URL mee te geven en de Mollie doorverwijzings-URL en het payment token terug te lezen.

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

**`mollie_return_url`** (optioneel, op `PlaceOrderInput`): de URL waarnaar Mollie de klant doorstuurt nadat de betaling is voltooid of geannuleerd. Als dit wordt weggelaten, gebruikt de extensie de standaard retour-URL van de winkel die is geconfigureerd in Magento Admin.

**`mollie_redirect_url`** (op `Order`): de door Mollie gehoste betaalpagina-URL. Stuur de klant door naar deze URL om de betaling te voltooien.

**`mollie_payment_token`** (op `Order`): een token dat deze betaling uniek identificeert. Sla dit token op zodat je `mollieProcessTransaction` kunt aanroepen wanneer de klant terugkeert.

---

### De transactie verwerken bij terugkeer

Nadat de klant terugkeert van de betaalpagina van Mollie, roep je `mollieProcessTransaction` aan om de betaalstatus te synchroniseren met Magento.

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

**Invoer:** `payment_token` is de waarde die door `placeOrder` wordt geretourneerd in het veld `mollie_payment_token`, of de queryparameter `payment_token` die door de extensie aan de retour-URL wordt toegevoegd.

**Responsvelden:**

| Veld | Type | Beschrijving |
|---|---|---|
| `paymentStatus` | `PaymentStatusEnum` | De Mollie-betaalstatus op het moment van de aanroep |
| `redirect_to_success_page` | `Boolean` | `true` wanneer de betaling is geslaagd en de klant de orderbevestiging moet zien |
| `redirect_to_cart` | `Boolean` | `true` wanneer de betaling is mislukt, geannuleerd of verlopen |
| `cart` | `Cart` | De herstelde winkelwagen, alleen aanwezig wanneer `redirect_to_cart` `true` is |

**`PaymentStatusEnum`-waarden:** `CREATED`, `OPEN`, `PENDING`, `AUTHORIZED`, `PAID`, `SHIPPING`, `COMPLETED`, `CANCELED`, `EXPIRED`, `REFUNDED`, `FAILED`, `ERROR`

Wanneer `redirect_to_cart` `true` is, reaktiveert de extensie de winkelwagen automatisch en retourneert deze in het veld `cart` zodat de klant de bestelling kan aanpassen zonder opnieuw te beginnen.

---

### Een winkelwagen handmatig herstellen

Als je een winkelwagen onafhankelijk van `mollieProcessTransaction` opnieuw moet activeren, gebruik je `mollieRestoreCart`.

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

Deze mutation accepteert het gemaskeerde winkelwagen-ID (dezelfde `cart_id`-string die door alle andere winkelwagen-mutations wordt gebruikt) en markeert de winkelwagen als actief. De mutation valideert dat de geauthenticeerde klant eigenaar is van de winkelwagen. Gastwinkelwagens kunnen worden hersteld zonder authenticatie.

---

### Een bestelling opzoeken via hash

`mollieCustomerOrder` haalt een volledige bestelling op via de versleutelde hash die aan de retour-URL is toegevoegd. Gebruik dit om besteldetails weer te geven op een headless bevestigingspagina zonder dat de klant hoeft te zijn ingelogd.

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

De parameter `hash` komt van de queryparameter `order_id` die door de extensie aan de retour-URL wordt toegevoegd. De resolver ontsleutelt de hash intern en retourneert een standaard `CustomerOrder`-object.

---

### Payment Link doorverwijzing

Wanneer een klant een betalingslink opent (verstuurd via de Second Chance Email of handmatig gegenereerd), roep je `molliePaymentLinkRedirect` aan om te bepalen welke actie moet worden ondernomen.

```graphql
mutation handlePaymentLink($order: String!) {
  molliePaymentLinkRedirect(order: $order) {
    redirect_url
    already_paid
    is_expired
  }
}
```

Het argument `order` is het versleutelde bestelling-ID dat is opgenomen in de URL van de betalingslink. De respons vertelt je of je:

- De klant naar `redirect_url` moet doorsturen om de betaling te voltooien
- Een bericht "al betaald" moet tonen wanneer `already_paid` `true` is
- Een bericht "verlopen" moet tonen wanneer `is_expired` `true` is

---

### Opgeslagen kaarten (Credit Card)

Opgeslagen kaarten zijn beschikbaar wanneer de Customers API is ingeschakeld voor Credit Card-betalingen in Magento Admin. Alle bewerkingen met opgeslagen kaarten vereisen een geauthenticeerd klanttoken in de `Authorization`-requestheader.

#### Opgeslagen kaarten weergeven

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

**Velden op `MollieSavedCard`:**

| Veld | Type | Beschrijving |
|---|---|---|
| `mandate_id` | `String!` | Het Mollie-mandaat-ID, gebruikt om de kaart in te trekken |
| `card_label` | `String!` | Kaartmerk, bijvoorbeeld `Visa` of `Mastercard` |
| `card_number_last4` | `String!` | Laatste vier cijfers van het kaartnummer |
| `card_expiry_date` | `String` | Vervaldatum in `MM/YYYY`-formaat |
| `card_holder` | `String` | Naam van de kaarthouder zoals opgeslagen bij Mollie |

#### Een opgeslagen kaart intrekken

```graphql
mutation revokeCard($mandateId: String!) {
  revokeMollieSavedCard(mandate_id: $mandateId) {
    success
  }
}
```

Het intrekken van een kaart trekt het onderliggende Mollie-mandaat in. De kaart kan niet meer worden gebruikt voor toekomstige betalingen. Bij succes retourneert de resolver `{ success: true }`.

---

### Apple Pay-validatie

Bij het implementeren van een aangepaste Apple Pay-knop in een headless storefront moet je de Apple Pay-handelaarssessie server-side valideren voordat je het betaalscherm toont. Gebruik `mollieApplePayValidation` om deze aanroep via de Mollie API te proxyen.

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

**Argumenten:**

| Argument | Type | Beschrijving |
|---|---|---|
| `validationUrl` | `String!` | De validatie-URL aangeleverd door het Apple Pay JS-event `onvalidatemerchant` |
| `domain` | `String` | Het te registreren domein; valt terug op de basis-URL van de winkel als dit wordt weggelaten |

Het veld `response` bevat de ruwe JSON-string die door Apple's servers wordt geretourneerd. Geef dit rechtstreeks door aan `session.completeMerchantValidation()` in je Apple Pay JS-sessiehandler.

Stel na validatie de betaalmethode in met `mollie_applepay_payment_token` met het token uit het Apple Pay JS-event `onpaymentauthorized`.

---

### Winkelwagenprijzen: betalingstoeslag

Wanneer er een betalingstoeslag (surcharge) is geconfigureerd voor de geselecteerde methode, verschijnt deze in de winkelwagentotalen.

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

`mollie_payment_fee` is `null` wanneer er geen toeslag is geconfigureerd voor de geselecteerde betaalmethode. Zie [Payment Fee](PAYMENT_FEE.md) voor configuratiedetails.

---

### Mollie Components in een headless context

Mollie Components is het ingebedde kaartinvoerformulier voor Credit Card-betalingen. In een headless storefront initialiseer je de Mollie JS-bibliotheek aan de client-side met behulp van de profiel-ID uit `storeConfig`.

1. Voer een query uit op `storeConfig { mollie { profile_id live_mode } }` bij het laden van de pagina.
2. Initialiseer de Mollie JS-bibliotheek: `Mollie(profileId, { testmode: !live_mode })`.
3. Maak componentvelden aan en mount ze (`cardNumber`, `cardHolder`, `expiryDate`, `verificationCode`) met `mollie.createComponent()`.
4. Roep bij het indienen van het formulier `mollie.createToken()` aan om een `mollie_card_token` te genereren.
5. Geef `mollie_card_token` mee in de mutation `setPaymentMethodOnCart`.
6. Ga verder met `placeOrder` zoals normaal - het token wordt aan de betaling gekoppeld en er is geen doorverwijzing naar een gehoste pagina nodig voor geldige getokeniseerde betalingen.

**Belangrijk:** Mollie Components vereist een geldige profiel-ID. Als `storeConfig.mollie.profile_id` `null` is, val dan terug op de standaard doorverwijzingsstroom.

---

### GraphQL-configuratie vereist in Magento Admin

Er is geen extra configuratie nodig om de GraphQL-endpoints in te schakelen. De schema-uitbreidingen zijn actief zodra de module is ingeschakeld en een geldige API-sleutel is geconfigureerd.

Voor specifieke functies:

- **Opgeslagen kaarten:** schakel **Save Cards** in onder **Stores → Configuration → Mollie → Payment Methods → Credit Card**
- **Apple Pay:** schakel Apple Pay in onder **Stores → Configuration → Mollie → Payment Methods → Apple Pay** en zorg dat je domein is geverifieerd in het Mollie Dashboard
- **Point of Sale-terminals:** schakel Point of Sale in onder **Stores → Configuration → Mollie → Payment Methods → Point Of Sale (POS)** en registreer je terminals in het Mollie Dashboard
- **Betalingstoeslag:** configureer een toeslag per methode - zie [Payment Fee](PAYMENT_FEE.md)

---

### GraphQL-foutafhandeling

GraphQL-fouten van Mollie-resolvers volgen het standaard foutformaat van Magento, met het foutbericht in de array `errors` van de respons.

Veelvoorkomende fouten en oorzaken:

| Fout | Oorzaak |
|---|---|
| `Missing "payment_token" input argument` | `mollieProcessTransaction` aangeroepen zonder een `payment_token` in de invoer |
| `No order found with token "..."` | Het payment token komt niet overeen met een bestelling; dit kan gebeuren als het token al is verbruikt of de bestelling is geannuleerd |
| `Order not found` | `molliePaymentLinkRedirect` heeft een ongeldig of verlopen versleuteld bestelling-ID ontvangen |
| `The current customer is not authorized.` | Een query voor opgeslagen kaarten of een intrekking is aangeroepen zonder een geldig klanttoken in de `Authorization`-header |
| `Saved cards are not enabled.` | `revokeMollieSavedCard` is aangeroepen maar de Customers API is niet ingeschakeld voor Credit Card in Magento Admin |
| `Required parameter "cart_id" is missing` | `mollieRestoreCart` aangeroepen zonder een `cart_id` in de invoer |

Wanneer `mollieProcessTransaction` `FAILED`, `CANCELED` of `EXPIRED` retourneert, is `redirect_to_cart` `true` en wordt de winkelwagen automatisch hersteld. Roep in dat geval `mollieRestoreCart` niet apart aan.

---

## REST API

### Overzicht van de REST checkout-stroom

De REST-integratie gebruikt de standaard winkelwagen- en bestelling-endpoints van Magento samen met Mollie-specifieke endpoints voor betalingsorkestratie.

1. Bouw de winkelwagen op via standaard Magento REST (artikelen, adressen, verzending)
2. Haal uitgever- en terminalmetadata op via `GET /rest/V1/mollie/payment-method/meta`
3. Stel de betaalmethode in via standaard Magento REST, met Mollie-specifieke velden in `additional_data`
4. Genereer een payment token vanuit de actieve winkelwagen
5. Plaats de bestelling via standaard Magento REST
6. Start de Mollie-transactie met het payment token - retourneert de Mollie checkout-URL
7. Stuur de klant door naar de checkout-URL
8. Haal bij terugkeer de bestelstatus op via hash of payment token
9. Reset de winkelwagen als de betaling is mislukt of geannuleerd

---

### Betaalmethode-metadata

Haal de uitgevers en terminals op die beschikbaar zijn per betaalmethode voordat je de betalingsstap toont.

```
GET /rest/V1/mollie/payment-method/meta
```

Geen authenticatie vereist. Respons is een array van methode-objecten:

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

### De betaalmethode instellen

Gebruik het standaard betaalmethode-endpoint van Magento en geef Mollie-specifieke waarden mee in `additional_data`.

```
PUT /rest/V1/carts/mine/selected-payment-method
```

Voor gastwinkelwagens: `PUT /rest/V1/guest-carts/:cartId/selected-payment-method`

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

**Mollie `additional_data`-velden:**

| Sleutel | Wanneer te gebruiken |
|---|---|
| `selected_issuer` | Het uitgever-`id` uit de metadata-respons, vereist voor iDEAL en andere op uitgevers gebaseerde methoden |
| `selected_terminal` | Het terminal-`id` uit de metadata-respons, vereist voor Point of Sale |
| `card_token` | Het token gegenereerd door Mollie Components (alleen Credit Card) |

---

### Een payment token genereren

Genereer een payment token vanuit de actieve winkelwagen. Doe dit voordat je de bestelling plaatst.

Voor geauthenticeerde klanten:

```
GET /rest/V1/carts/mine/mollie/payment-token
```

Voor gastwinkelwagens:

```
GET /rest/V1/guest-carts/:cartId/mollie/payment-token
```

De respons is een gewone tekststring met het token. Sla dit op - je hebt het nodig om de transactie te starten nadat de bestelling is geplaatst.

---

### De bestelling plaatsen

Plaats de bestelling via het standaard Magento-endpoint. De extensie koppelt het door jou gegenereerde payment token automatisch aan de nieuwe bestelling tijdens het indienen van de bestelling.

```
POST /rest/V1/carts/mine/payment-information
```

Voor gastwinkelwagens: `POST /rest/V1/guest-carts/:cartId/payment-information`

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

De respons is het Magento-bestelling-ID (integer).

---

### De Mollie-transactie starten

Na het plaatsen van de bestelling start je de Mollie-transactie met het payment token. Dit maakt de betaling aan bij Mollie en retourneert de URL waarnaar de klant moet worden doorgestuurd.

```
POST /rest/V1/mollie/transaction/start
```

```json
{
  "token": "your-payment-token"
}
```

De respons is een gewone tekststring met de Mollie checkout-URL. Stuur de klant door naar deze URL om de betaling te voltooien.

---

### De terugkeer verwerken

Nadat de klant terugkeert van de betaalpagina van Mollie, haal je de bestelling op om de betaaluitkomst te bepalen. De retour-URL bevat een versleutelde `order_id`-hash en een parameter `payment_token`.

Ophalen via hash (anoniem, geen authenticatie vereist):

```
GET /rest/V1/mollie/get-order/by-hash/:hash
POST /rest/V1/mollie/get-order/by-hash/:hash
```

Ophalen via payment token:

```
POST /rest/V1/mollie/get-order/by-payment-token/:token
```

Beide retourneren een Magento-bestelobj. Controleer de status van de bestelling om te bepalen of je een bevestigingspagina moet tonen of de klant terug naar de winkelwagen moet sturen.

---

### De winkelwagen resetten na een mislukte betaling

Als de betaling is mislukt of geannuleerd, herstel dan de winkelwagen zodat de klant het opnieuw kan proberen.

```
POST /rest/V1/mollie/reset-cart/:hash
```

De `hash` is het versleutelde bestelling-ID uit de retour-URL. Geen requestbody vereist. Bij succes wordt de oorspronkelijke winkelwagen opnieuw geactiveerd en gekoppeld aan de sessie van de klant.

---

### Payment Link doorverwijzing

Wanneer een klant een betalingslink opent, bepaal je welke actie moet worden ondernomen voordat je hem doorstuurt.

```
GET /rest/V1/mollie/get-payment-link-redirect/:hash
```

De `hash` is het versleutelde bestelling-ID uit de URL van de betalingslink.

Respons:

```json
{
  "redirect_url": "https://...",
  "already_paid": false,
  "is_expired": false
}
```

Stuur de klant door naar `redirect_url` om de betaling te voltooien. Toon een passend bericht wanneer `already_paid` of `is_expired` `true` is.

---

### Opgeslagen kaarten via REST

Bekijk en verwijder opgeslagen kaarten voor de geauthenticeerde klant.

Opgeslagen kaarten weergeven (vereist `Authorization`-header):

```
GET /rest/V1/mollie/customer/me/saved-cards
```

Een opgeslagen kaart verwijderen:

```
DELETE /rest/V1/mollie/customer/me/saved-cards/:mandateId
```

Het `mandateId` komt uit de weergaverespons. Het verwijderen van een kaart trekt het onderliggende Mollie-mandaat in.

---

## Volgende stappen

- [Installation](INSTALLATION.md) - De extensie installeren en inschakelen
- [API Keys](API_KEYS.md) - Je Mollie API-sleutel configureren
- [Credit Card Payments](CREDIT_CARD.md) - Mollie Components en configuratie van opgeslagen kaarten
- [Apple Pay](APPLE_PAY.md) - Apple Pay-handelaarsvalidatie en domeinregistratie
- [Point of Sale](POINT_OF_SALE.md) - Terminalconfiguratie voor persoonlijke betalingen
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan betaalmethoden
- [Second Chance Email](SECOND_CHANCE_EMAIL.md) - Genereren en configureren van betalingslinks
