# Payment Link / Admin Payment

Payment Link is een betaalmethode die uitsluitend voor beheerders beschikbaar is. Deze verschijnt niet in de storefront-checkout. In plaats daarvan stelt het een Magento Admin-gebruiker in staat om namens een klant een order aan te maken en een door Mollie gehoste betaallink te genereren die via elk kanaal (e-mail, sms, enz.) naar de klant kan worden gestuurd.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd en ingeschakeld - zie [Installation](INSTALLATION.md)
- Een geldige live API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)

## Configuratie

Ga naar **Stores → Configuration → Mollie → Payment Methods → Payment Link / Admin Payment**.

| Veld | Omschrijving |
|---|---|
| **Enabled** | Stel in op **Yes** om de methode beschikbaar te maken bij het aanmaken van orders in Magento Admin |
| **Add Link to Payment Details** | Wanneer **Yes**, wordt de betaallink opgeslagen in de betalingsinformatie van de order en weergegeven in het betaaldetailsblok op de orderweergavepagina |
| **Payment Message / Link** | De berichtsjabloon die aan het betaalinformatieblok wordt toegevoegd. Gebruik `%link%` waar je wilt dat de URL verschijnt. Alleen zichtbaar wanneer **Add Link to Payment Details** op **Yes** staat |
| **Allow orders to be marked as paid manually** | Wanneer **Yes**, verschijnt er een knop **Mark as paid** op de orderweergavepagina - zie [Een order als betaald markeren](#een-order-als-betaald-markeren) |
| **Status After Creation** | De orderstatus die direct na het aanmaken van de order door de beheerder wordt toegewezen. Gebruik een aangepaste status (bijvoorbeeld `waiting_for_payment`) om deze orders te onderscheiden van standaard openstaande orders in het orderoverzicht |
| **Capture method** | Capture-gedrag; zie [Order Management](ORDER_MANAGEMENT.md) voor details |

### Aangepaste URL voor betaallink

Standaard verwijst de gegenereerde link naar `jouwdomein.com/mollie/checkout/paymentlink?order=...`. Voor headless storefronts of aangepaste checkout-flows kun je de basis-URL overschrijven.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → PWA Storefront Integration**
2. Stel **Use custom payment link url?** in op **Yes**
3. Voer de aangepaste URL in bij **Custom payment link url**. Gebruik `{{order}}` als placeholder voor de versleutelde orderidentificator:

   ```
   https://mijn-headless-frontend.com/checkout/payment-link/{{order}}
   ```

   Als `{{order}}` ontbreekt, wordt de versleutelde identificator aan het einde van de URL toegevoegd.

4. Klik op **Save Config** en leeg de cache.

Wanneer de klant een aangepaste URL opent, is je frontend verantwoordelijk voor het verwerken van de betaallink - zie [Payment Link Redirect](HEADLESS.md#payment-link-redirect) in de headless integratiedocumentatie.

## Een Admin Payment aanmaken

### 1. Het formulier voor nieuwe orders openen

Ga naar **Sales → Orders** en klik op **Create New Order**.

Selecteer een bestaande klant of maak een gastorder aan.

### 2. De order samenstellen

Voeg producten, een factuuradres en een verzendadres toe zoals je dat voor elke beheerdersorder zou doen. Een verzendmethode is vereist tenzij alle artikelen virtueel zijn.

### 3. Payment Link / Admin Payment selecteren

Selecteer in de sectie **Payment Method** de optie **Payment Link / Admin Payment**.

Hieronder verschijnt een meervoudige keuzelijst **Payment Methods**. Gebruik deze om te beperken welke Mollie-betaalmethoden de klant kan kiezen wanneer hij de link opent. Laat alle methoden uitgeschakeld om elke methode toe te staan die actief is op je Mollie-account.

### 4. De order plaatsen

Klik op **Submit Order**.

De order wordt aangemaakt in Magento met de status die is geconfigureerd in **Status After Creation**. Mollie wordt op dit punt niet gecontacteerd - er bestaat nog geen transactie.

### 5. De betaallink ophalen en versturen

Open de zojuist aangemaakte order. De betaallink is op twee plaatsen beschikbaar:

- **Blok Betalingsinformatie** (als **Add Link to Payment Details** is ingeschakeld): de link verschijnt inline in de sectie met betalingsdetails met de berichtsjabloon die je hebt geconfigureerd.
- **Payment & Shipping Information → Payment Method**: de link wordt weergegeven naast de naam van de methode.

Kopieer de link en stuur deze naar de klant via e-mail, sms of een ander kanaal. Er is geen ingebouwd mechanisme om de link automatisch te verzenden - gebruik [Second Chance Email](SECOND_CHANCE_EMAIL.md) als je geautomatiseerde betalingsherinneringen wilt sturen nadat de eerste link is verzonden.

## Wat er gebeurt wanneer de klant de link opent

Het linkdoel op `mollie/checkout/paymentlink` ontsleutelt de orderidentificator en bepaalt de volgende actie:

| Conditie | Resultaat |
|---|---|
| Order is nog niet betaald | Klant wordt doorgestuurd naar de door Mollie gehoste betaalpagina |
| Order heeft al de status `processing` of `complete` | Klant ziet "Uw order is al betaald" en wordt doorgestuurd naar de startpagina van de winkel |
| Betaallink is verlopen | Klant ziet "Uw betaallink is verlopen" en wordt doorgestuurd naar de startpagina van de winkel |

Nadat de klant de betaling bij Mollie heeft voltooid, ontvangt de extensie een webhook en wordt de orderstatus bijgewerkt naar **Processing** en wordt automatisch een factuur aangemaakt (overeenkomend met het gedrag voor alle andere Mollie-betaalmethoden).

Als de klant de betaling bij Mollie annuleert, kan hij de link opnieuw openen om het opnieuw te proberen. De link blijft geldig totdat deze verloopt.

## Een order als betaald markeren

Wanneer **Allow orders to be marked as paid manually** is ingeschakeld, verschijnt er een knop **Mark as paid** op de orderweergavepagina voor Payment Link-orders die nog kunnen worden geannuleerd.

Klikken op **Mark as paid**:

1. Annuleert de originele Payment Link-order
2. Maakt een nieuwe order aan op basis van dezelfde artikelen via de betaalmethode voor cheque/postwissel
3. Factureert de nieuwe order direct en stelt deze in op **Processing**

Gebruik dit wanneer een klant buiten Mollie om heeft betaald (bijvoorbeeld via een bankoverschrijving die direct is bevestigd) en je de order in Magento moet sluiten.

## Volgende stappen

- [Second Chance Email](SECOND_CHANCE_EMAIL.md) - Geautomatiseerde betalingsherinneringen versturen
- [Order Management](ORDER_MANAGEMENT.md) - Orderstatussen, facturering en capture-configuratie
- [Payment Methods](PAYMENT_METHODS.md) - Overzicht van alle betaalmethoden
- [Headless / GraphQL & REST](HEADLESS.md) - Betaallinks verwerken in een headless storefront
