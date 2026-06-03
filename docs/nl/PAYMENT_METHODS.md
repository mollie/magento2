# Betaalmethoden

Deze pagina beschrijft alle betaalmethoden die worden ondersteund door Mollie Payments voor Magento 2, hoe je ze inschakelt en configureert, de instellingen die alle methoden delen, en de Methods API-functie die de checkout-lijst automatisch filtert.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd - zie [Installation](INSTALLATION.md)
- Een live- en/of test-API-sleutel is opgeslagen in Magento Admin - zie [API Keys](API_KEYS.md)

## Ondersteunde methoden

De extensie bevat de volgende betaalmethoden standaard:

| Methode | Config-sleutel |
|---|---|
| Alma | `mollie_methods_alma` |
| Apple Pay | `mollie_methods_applepay` |
| Bancomat Pay | `mollie_methods_bancomatpay` |
| Bancontact | `mollie_methods_bancontact` |
| Bank Transfer | `mollie_methods_banktransfer` |
| Belfius | `mollie_methods_belfius` |
| Billie | `mollie_methods_billie` |
| Bizum | `mollie_methods_bizum` |
| Blik | `mollie_methods_blik` |
| Credit Card | `mollie_methods_creditcard` |
| SEPA Direct Debit | `mollie_methods_directdebit` |
| EPS | `mollie_methods_eps` |
| Express Components | `mollie_methods_expresscomponents` |
| Giftcard | `mollie_methods_giftcard` |
| Google Pay | `mollie_methods_googlepay` |
| iDEAL / Wero | `mollie_methods_ideal` |
| in3 | `mollie_methods_in3` |
| KBC/CBC | `mollie_methods_kbc` |
| Klarna | `mollie_methods_klarna` |
| MB Way | `mollie_methods_mbway` |
| MobilePay | `mollie_methods_mobilepay` |
| Multibanco | `mollie_methods_multibanco` |
| MyBank | `mollie_methods_mybank` |
| Pay by Bank | `mollie_methods_paybybank` |
| Payconiq | `mollie_methods_payconiq` |
| Payment Link | `mollie_methods_paymentlink` |
| PayPal | `mollie_methods_paypal` |
| Paysafecard | `mollie_methods_paysafecard` |
| Point of Sale (POS) | `mollie_methods_pointofsale` |
| Przelewy24 | `mollie_methods_przelewy24` |
| Riverty | `mollie_methods_riverty` |
| Satispay | `mollie_methods_satispay` |
| Sofort | `mollie_methods_sofort` |
| Swish | `mollie_methods_swish` |
| Trustly | `mollie_methods_trustly` |
| TWINT | `mollie_methods_twint` |
| Vipps | `mollie_methods_vipps` |
| Voucher | `mollie_methods_voucher` |

Een methode moet actief zijn in je Mollie-accountprofiel voordat deze in de checkout verschijnt, ongeacht de instelling in Magento Admin. Log in op het [Mollie Dashboard](https://www.mollie.com/dashboard) en bevestig dat elke methode is ingeschakeld in je profiel.

## Een methode in- of uitschakelen

Elke betaalmethode wordt afzonderlijk in- of uitgeschakeld. De stappen zijn voor elke methode identiek - vervang "iDEAL / Wero" in het voorbeeld door de naam van de methode die je wilt wijzigen.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Open de sectie voor de methode (bijvoorbeeld **iDEAL / Wero**)
3. Stel **Enabled** in op **Yes** of **No**
4. Klik op **Save Config** en leeg de cache - wijzigingen zijn pas zichtbaar voor klanten nadat de cache is geleegd

**Tip:** De Methods API (zie hieronder) kan een methode bij de checkout automatisch verbergen wanneer deze niet van toepassing is voor het land van de klant of het totaal van de winkelwagen, zodat je methoden niet altijd handmatig hoeft uit te schakelen.

## Algemene instellingen

Elke methode toont dezelfde kernset van velden zodra **Enabled** is ingesteld op **Yes**.

### Titel

Het veld **Title** bepaalt het label dat aan de klant wordt getoond bij de checkout. Pas het aan zodat het overeenkomt met de taal of huisstijl van je winkel.

1. Open de methodesectie onder **Stores → Configuration → Mollie → Payment Methods**
2. Bewerk het veld **Title**
3. Klik op **Save Config** en leeg de cache

### Omschrijving

Het veld **Description** stelt de betalingsomschrijving in die naar Mollie wordt gestuurd en die verschijnt in het Mollie Dashboard en op bankafschriften. De standaardwaarde is `{ordernumber}`. De volgende placeholders zijn beschikbaar:

| Placeholder | Vervangen door |
|---|---|
| `{ordernumber}` | Het Magento-orderincrementnummer |
| `{storename}` | De winkelnaam |

### Landbeperkingen

Beperk een methode tot klanten in specifieke landen.

1. Open de methodesectie onder **Stores → Configuration → Mollie → Payment Methods**
2. Stel **Payment from Applicable Countries** in op **Specific Countries**
3. Selecteer de toegestane landen in **Payment from Specific Countries**
4. Klik op **Save Config** en leeg de cache

Wanneer de Methods API is ingeschakeld, handhaaft Mollie ook landbeperkingen aan de serverzijde, waardoor een hier handmatig geconfigureerde lijst als extra beveiliging fungeert.

### Limieten voor ordertotaal

Toon een methode alleen wanneer de winkelwagen binnen een bepaald bereik valt.

1. Open de methodesectie onder **Stores → Configuration → Mollie → Payment Methods**
2. Voer een waarde in bij **Minimum Order Total** en/of **Maximum Order Total** (laat leeg voor geen limiet)
3. Klik op **Save Config** en leeg de cache

De waarden worden vergeleken met het ordertotaal in de basisvaluta van de winkel.

### Sorteervolgorde

Het veld **Sort Order** bepaalt waar een methode verschijnt ten opzichte van andere betaalmethoden in de checkout. Lagere nummers verschijnen eerst. Methoden met dezelfde sorteervolgorde worden alfabetisch op titel gesorteerd.

1. Open de methodesectie onder **Stores → Configuration → Mollie → Payment Methods**
2. Voer een getal in bij **Sort Order**
3. Klik op **Save Config** en leeg de cache

### Betalingstoeslag

Elke methode ondersteunt een optionele toeslag die aan het ordertotaal wordt toegevoegd wanneer de klant deze selecteert. Zie [Payment Fee](PAYMENT_FEE.md) voor configuratiedetails.

## De Methods API

De Methods API raadpleegt Mollie bij het laden van de checkout om alleen de betaalmethoden op te halen die geldig zijn voor het factuurland van de klant en het huidige totaal van de winkelwagen. Methoden die Mollie zou weigeren, worden verborgen voordat de klant ze ziet.

De Methods API is standaard ingeschakeld. De instelling wijzigen:

1. Ga naar **Stores → Configuration → Mollie → Developer Settings**
2. Open **Advanced**
3. Stel **Enable the methods API** in op **Yes** of **No**
4. Klik op **Save Config** en leeg de cache

Wanneer de Methods API is uitgeschakeld, toont de checkout elke methode die je in Magento Admin hebt ingeschakeld. Je bent dan zelf verantwoordelijk voor het handmatig configureren van land- en ordertotaalbeperkingen om te voorkomen dat klanten een methode selecteren die Mollie zal weigeren.

**Belangrijk:** Het uitschakelen van de Methods API bespaart één API-aanroep per checkout-laden maar verwijdert het automatische filteren. Schakel het alleen uit als je specifieke land- en ordertotaalregels hebt geconfigureerd voor elke actieve methode.

## Betaaliconen

Je kunt het logo van de betaalmethode naast de methodetitel bij de checkout tonen of verbergen.

1. Ga naar **Stores → Configuration → Mollie → General → Settings**
2. Stel **Show Icons** in op **Yes** of **No**
3. Klik op **Save Config** en leeg de cache

## Standaard geselecteerde methode

Je kunt een betaalmethode vooraf selecteren wanneer de klant de betaalstap bereikt. Deze instelling is alleen beschikbaar op het niveau van de store view.

1. Ga naar **Stores → Configuration → Mollie → General → Settings**
2. Gebruik de schakelaar **Store View** (linksboven op de configuratiepagina) om de juiste store view te selecteren
3. Stel **Default selected method** in op de methode die je vooraf geselecteerd wilt hebben
4. Klik op **Save Config** en leeg de cache

## Methoden testen in sandboxmodus

Stel de extensie in op testmodus en gebruik de testgegevens van Mollie om betalingsresultaten te simuleren zonder echte transacties te verwerken.

1. Stel **Modus** in op **Test** in **Stores → Configuration → Mollie → General → Mollie Configuration** - zie [API Keys](API_KEYS.md)
2. Schakel de methode in die je wilt testen
3. Plaats een testorder en selecteer de methode bij de checkout
4. Gebruik op de door Mollie gehoste betaalpagina de testgegevens uit [Mollie's testdocumentatie](https://docs.mollie.com/docs/testing) om betaalde, geannuleerde of mislukte uitkomsten te simuleren

De webhook wordt aangeroepen na elke gesimuleerde betaling. Controleer of de orderstatus in Magento Admin correct wordt bijgewerkt. Als de status niet verandert, controleer dan de webhook-configuratie in [Configuration](CONFIGURATION.md).

## Methodespecifieke opmerkingen

### Bank Transfer

Bank Transfer heeft een extra veld **Due Days** (standaard: 14) dat instelt hoeveel dagen de klant heeft om de overboeking te voltooien voordat Mollie de betaling laat verlopen. Het geldige bereik is 1 tot 100 dagen.

Bank Transfer heeft ook een eigen veld **Status Pending**. Mollie adviseert een aangepaste orderstatus te gebruiken in plaats van de standaard Magento openstaande status, die orders automatisch kan annuleren voordat het betalingsvenster sluit.

### Giftcard en iDEAL / Wero

Beide methoden ondersteunen een veld **Issuer List Style** dat bepaalt hoe de lijst met uitgevende instellingen (kaartmerken of banken) wordt weergegeven bij de checkout:

- **Radio** - weergegeven als een lijst met keuzerondjes
- **Dropdown** - weergegeven als een dropdown-selectie
- **None** - de uitgevende instelling wordt geselecteerd op de door Mollie gehoste pagina

### Voucher

De Voucher-methode vereist een veld **Category** dat de order classificeert voor Belgische maaltijd-, eco- en cadeaubonregelingen. Stel **Category** in op **Custom attribute** als je catalogus producten uit gemengde categorieën bevat, en selecteer vervolgens een productattribuut waarvan de waarden overeenkomen met `meal`, `eco`, `gift` of `none`.

### Apple Pay

Apple Pay is alleen zichtbaar voor klanten wiens apparaat Apple Pay heeft geconfigureerd en die de checkout via HTTPS bereiken. Zie [Apple Pay](APPLE_PAY.md) voor de directe integratie (inline knop op productpagina's en minicart) en de configuratie van de knopstijl.

### Google Pay

Google Pay is standaard uitgeschakeld omdat het niet wordt beheerd door de Methods API. Schakel het handmatig in en configureer landbeperkingen om ervoor te zorgen dat het alleen verschijnt waar Google Pay wordt ondersteund. Zie [Google Pay](GOOGLE_PAY.md) voor volledige configuratiedetails.

### Klarna

Klarna gebruikt standaard handmatige capture, gestart bij verzending. Zie [Klarna](KLARNA.md) voor vereisten voor orderregels, capture-configuratie en factuur-e-mailgedrag.

### Point of Sale

Point of Sale is bedoeld voor gebruik in de winkel en is beschikbaar voor Magento Admin-gebruikers die orders plaatsen vanuit Magento Admin. Het ondersteunt naast de standaard land- en ordertotaalvelden ook een beperking op **Allowed Customer Groups**. Zie [Point of Sale](POINT_OF_SALE.md) voor volledige installatie-instructies.

### Payment Link

Payment Link is een methode die alleen voor beheerders beschikbaar is en niet in de storefront-checkout verschijnt. Het genereert een Mollie-betaallink die je handmatig naar een klant kunt sturen. Zie [Payment Link / Admin Payment](PAYMENT_LINK.md) voor volledige configuratie- en workflowdetails.

### SEPA Direct Debit

SEPA Direct Debit is standaard uitgeschakeld en vereist expliciete activering in je Mollie-account. Schakel het in Admin alleen in nadat je hebt bevestigd dat het actief is in je Mollie-profiel.

### Express Components

Express Components is verborgen in Magento Admin totdat het eenmalig via de CLI is geactiveerd:

```bash
php bin/magento config:set payment/mollie_methods_expresscomponents/active 1
php bin/magento cache:flush
```

Daarna configureer je het onder **Stores → Configuration → Mollie → Payment Methods → Express Components**. Zie [Express Components](EXPRESS_COMPONENTS.md) voor plaatsing, capture-gedrag en beperkingen voor live gebruik.

## Volgende stappen

- [Apple Pay](APPLE_PAY.md) - Directe integratie, knoppen op productpagina's en minicart-knop
- [Google Pay](GOOGLE_PAY.md) - Google Pay-installatie en -configuratie
- [Credit Card Payments](CREDIT_CARD.md) - Mollie Components, capture-modus en opgeslagen kaarten
- [Express Components](EXPRESS_COMPONENTS.md) - Express checkout-widgets op winkelwagen, minicart en checkout
- [Klarna](KLARNA.md) - Orderregels, capture en facturatieconfiguratie
- [Point of Sale](POINT_OF_SALE.md) - Betalingsinstallatie in de winkel
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan een betaalmethode
- [Configuration](CONFIGURATION.md) - Algemene instellingen, taal en orderstatus
- [API Keys](API_KEYS.md) - Schakelen tussen test- en livemodus
