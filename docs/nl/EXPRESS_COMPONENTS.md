# Express Components

Express Components voegt door Mollie gehoste express checkout-widgets toe aan de standaard Magento-storefront. In deze module wordt de functie gerenderd op de winkelwagenpagina, in de minicart en op de betalingsstap van het afrekenen.

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
| **Title** | Label dat voor de betaalmethode wordt gebruikt |
| **Description** | Betalingsomschrijving die naar Mollie wordt gestuurd. Ondersteunt de standaard placeholders zoals `{ordernumber}` en `{storename}` |
| **Capture method** | Bepaalt of de betaling automatisch of handmatig wordt vastgelegd |
| **Days to expire** | Optioneel vervaltermijn in dagen. Laat leeg om de standaardvervaldatum van 28 dagen te gebruiken |
| **Payment from Applicable Countries** / **Payment from Specific Countries** | Beperk beschikbaarheid op facturatieland |
| **Minimum Order Total** / **Maximum Order Total** | Beperk beschikbaarheid op orderbedrag |
| **Sort Order** | Bepaalt de positie van de methode ten opzichte van andere betaalmethoden |

Voor capture-gedrag na autorisatie, zie [Order Management](ORDER_MANAGEMENT.md).

## Storefront-gedrag

Wanneer de methode is ingeschakeld, rendert de module Express Components op drie plaatsen:

- **Winkelwagenpagina**: binnen het gebied met afrekenmethoden
- **Minicart**: wanneer de minicart wordt geopend en de winkelwagen niet leeg is
- **Checkout**: voor de reguliere lijst met betaalmethoden

### Winkelwagen en minicart

Op de winkelwagenpagina en in de minicart wordt het component aangemaakt als een express checkout-stroom voor de verzendkeuze. In deze plaatsing gebruikt de sessie het subtotaal van de winkelwagen inclusief belasting, niet het uiteindelijke totaalbedrag.

De widget is geconfigureerd voor iDEAL Express in deze plaatsingen. Apple Pay en Google Pay zijn daar expliciet uitgeschakeld.

### Checkout

Op de betalingsstap van het afrekenen wordt de widget geinitialiseerd nadat de standaard afrekengegevens beschikbaar zijn. Voor gastafrekenen wacht de module totdat de klant een e-mailadres heeft ingevoerd voordat de sessie wordt aangemaakt.

In deze plaatsing gebruikt de sessie het volledige totaalbedrag, inclusief verzendkosten en andere totaalonderdelen die op het moment van afrekenen al bekend zijn. De iDEAL Express-optie is hier uitgeschakeld omdat de verzendmethode al is geselecteerd.

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
