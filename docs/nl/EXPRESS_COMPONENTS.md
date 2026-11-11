# Express Components

Express Components voegt door Mollie gehoste express checkout-widgets toe aan de standaard Magento-storefront. In deze module wordt de functie gerenderd op de productpagina, de winkelwagenpagina, in de minicart, op de verzendstap en op de betalingsstap van het afrekenen.

## Vereisten

- Mollie Payments for Magento 2 is geinstalleerd en ingeschakeld
- Een live Mollie API-sleutel is geconfigureerd
- Mollie Support heeft Express Components ingeschakeld voor die API-sleutel

**Belangrijk:** Express Components ondersteunt geen testmodus. De Magento-configuratie inschakelen alleen is niet voldoende als de functie niet is ingeschakeld voor je live API-sleutel aan Mollie's kant.

## Eerste activering

De configuratiekaart van Express Components is verborgen in Magento Admin totdat de methode eenmalig is geactiveerd. Schakel deze eerst in via de CLI:

```bash
php bin/magento config:set payment/mollie_methods_expresscomponents/active 1
php bin/magento cache:flush
```

Ga daarna naar **Stores → Configuration → Mollie → Payment Methods** en configureer **Express Components** zoals elke andere Mollie-betaalmethode.

## Configuratie

De methode biedt de volgende instellingen onder **Stores → Configuration → Mollie → Payment Methods → Express Components**:

| Veld | Beschrijving |
|---|---|
| **Enabled** | Schakelt de frontend-widgets in of uit |
| **Shipping options and product page (beta)** | Laat Mollie het adres verzamelen en je verzendmethoden aanbieden buiten de betalingsstap, en toont de knoppen op de verzendstap en de productpagina. Schakel dit alleen in als Mollie de bèta voor klantgegevens en verzendopties voor je account heeft geactiveerd |
| **Title** | Label dat voor de betaalmethode wordt gebruikt |
| **Description** | Betalingsomschrijving die naar Mollie wordt gestuurd. Ondersteunt de standaard placeholders zoals `{ordernumber}` en `{storename}` |
| **Capture method** | Bepaalt of de betaling automatisch of handmatig wordt vastgelegd |
| **Days to expire** | Optioneel vervaltermijn in dagen. Laat leeg om de standaardvervaldatum van 28 dagen te gebruiken |
| **Payment from Applicable Countries** / **Payment from Specific Countries** | Beperk beschikbaarheid op facturatieland |
| **Minimum Order Total** / **Maximum Order Total** | Beperk beschikbaarheid op orderbedrag |
| **Sort Order** | Bepaalt de positie van de methode ten opzichte van andere betaalmethoden |

Voor capture-gedrag na autorisatie, zie [Order Management](ORDER_MANAGEMENT.md).

## Storefront-gedrag

Wanneer de methode is ingeschakeld, rendert de module Express Components op vijf plaatsen:

- **Productpagina**: bij de knop In winkelwagen
- **Winkelwagenpagina**: binnen het gebied met afrekenmethoden
- **Minicart**: wanneer de minicart wordt geopend en de winkelwagen niet leeg is
- **Verzendstap**: boven het adresformulier in de eerste stap van het afrekenen
- **Checkout**: voor de reguliere lijst met betaalmethoden

De productpagina, de verzendstap en de verzendopties zijn alleen beschikbaar als **Shipping options and product page (beta)** is ingeschakeld. Zonder deze instelling rekent de winkelwagenpagina het totaalbedrag inclusief de geschatte verzendkosten af, zoals voorheen, en wacht de module bij gasten tot ze een e-mailadres hebben ingevuld.

### Productpagina

Op de productpagina rekent de klant alleen het getoonde product af, met de gekozen hoeveelheid en opties. De winkelwagen van de klant blijft ongewijzigd. De module bewaart hiervoor geen winkelwagen in de database: de bestelling wordt opgebouwd in het geheugen en pas opgeslagen wanneer Mollie de betaling bevestigt via de webhook. Bij elke wijziging van hoeveelheid of opties wordt een nieuwe sessie aangemaakt.

Zolang verplichte opties, zoals de maat van een configureerbaar product, niet gekozen zijn, worden de knoppen niet getoond.

### Winkelwagen, minicart en verzendstap

Op deze plaatsen is nog geen verzendmethode gekozen. De sessie gebruikt daarom het subtotaal van de winkelwagen inclusief belasting. Mollie vraagt de klant om het verzendadres en, als dat ontbreekt, het e-mailadres en factuuradres. Gasten kunnen dus direct afrekenen zonder eerst hun e-mailadres in te vullen.

Zodra de klant een adres kiest, vraagt Mollie de verzendopties op bij de winkel via `mollie/express/shippingOptions`. De module geeft dezelfde verzendmethoden terug als Magento voor dat adres berekent. Mollie telt de gekozen verzendkosten op bij het totaalbedrag, en de bestelling krijgt dezelfde verzendmethode. Zijn er geen verzendmethoden voor het adres, dan toont Mollie dat het adres niet wordt ondersteund.

Mollie moet de winkel kunnen bereiken voor de verzendopties, net als voor de webhook. Staan webhooks uit in testmodus onder **Stores → Configuration → Mollie → General**, dan worden er geen verzendopties aangeboden.

Apple Pay en Google Pay zijn in deze plaatsingen expliciet uitgeschakeld.

### Checkout

Op de betalingsstap van het afrekenen wordt de widget geinitialiseerd nadat de standaard afrekengegevens beschikbaar zijn. Voor gastafrekenen wacht de module totdat de klant een e-mailadres heeft ingevoerd voordat de sessie wordt aangemaakt.

In deze plaatsing gebruikt de sessie het volledige totaalbedrag, inclusief verzendkosten en andere totaalonderdelen die op het moment van afrekenen al bekend zijn. Apple Pay en Google Pay zijn hier wel beschikbaar.

## Capture en afwikkeling

Express Components ondersteunt dezelfde capture-modus-instelling die in de betaalmethodeconfiguratie wordt aangeboden:

- **Autocapture**: bedragen worden automatisch vastgelegd na autorisatie
- **Manual capture**: capture wordt later getriggerd vanuit Magento op basis van je facturerings- of verzendstroom

Als je manual capture gebruikt, valideer dan de volledige workflow in een live omgeving voordat je deze voor klanten inschakelt, omdat Express Components zelf geen sandboxmodus heeft.

## Beperkingen

- De functie is alleen live en moet door Mollie Support worden ingeschakeld voor de actieve API-sleutel
- De Admin-configuratiekaart blijft verborgen totdat de methode eenmalig via CLI is geactiveerd
- De module biedt geen toeslag-instellingen voor Express Components. Zie [Payment Fee](PAYMENT_FEE.md)

## Volgende stappen

- [Payment Methods](PAYMENT_METHODS.md) - Overzicht van alle Mollie-methoden
- [Order Management](ORDER_MANAGEMENT.md) - Capture-modi, facturering en terugbetalingen
- [Payment Fee](PAYMENT_FEE.md) - Toeslagondersteuning en beperkingen
- [Troubleshooting](TROUBLESHOOTING.md) - Algemene diagnostiek voor checkout en webhook
