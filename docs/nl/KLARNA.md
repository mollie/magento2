# Klarna Betalingen

Deze pagina beschrijft hoe je Klarna inschakelt in Mollie Payments voor Magento 2, capture-gedrag configureert, orders beheert en terugbetalingen afhandelt.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd en een API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)
- Klarna is geactiveerd op je Mollie-account. Log in op het [Mollie Dashboard](https://www.mollie.com/dashboard) en ga naar **Payment methods** om activatie aan te vragen indien nodig.

## Over Klarna

Klarna is een koop-nu-betaal-later-betaalmethode waarmee klanten kunnen kiezen hoe ze betalen bij de checkout: direct, na levering of in termijnen. Mollie toont de Klarna-optie die beschikbaar is voor het land, het factuuradres en het winkelwagentotaal van de klant. Je hoeft niet elk Klarna-product afzonderlijk te integreren - Mollie bepaalt welk product wordt aangeboden op basis van geschiktheid.

Klarna vereist orderregels (afzonderlijke regelitems met namen, aantallen en eenheidsprijzen) bij elke transactie. De extensie bouwt deze automatisch op uit de Magento-order.

## Klarna inschakelen

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Klap **Klarna** uit
3. Zet **Enabled** op **Yes**
4. Pas optioneel het veld **Title** aan - dit is het label dat klanten zien bij de checkout (standaard: `Pay with Klarna.`)
5. Klik op **Save Config** en leeg de cache - Klarna verschijnt niet bij de checkout totdat de cache is geleegd

## Capture-modus

Klarna-betalingen worden geautoriseerd op het moment dat de klant de checkout voltooit. Middelen worden pas geïnd wanneer je de autorisatie capturet. De extensie ondersteunt twee capture-modi.

### Handmatige capture (standaard)

De standaard capture-modus voor Klarna is **Manual capture** met **When to capture?** ingesteld op **On shipment**. Dit sluit aan bij de standaard Klarna-flow: de klant wordt pas belast wanneer zijn goederen worden verzonden.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Klarna** uit
2. Bevestig dat **Capture method** is ingesteld op **Manual capture**
3. Zet **When to capture?** op:
   - **On shipment** - de extensie capturet de autorisatie en maakt automatisch een factuur aan wanneer je een zending aanmaakt in Magento Admin. Dit is de aanbevolen optie voor Klarna.
   - **On invoice** - de capture wordt naar Mollie gestuurd wanneer je handmatig een factuur aanmaakt voor de order
4. Klik op **Save Config** en leeg de cache

**Belangrijk:** Als je **On shipment** kiest maar nooit een zendingsrecord aanmaakt in Magento Admin, wordt de autorisatie nooit gecaptured. Stel een **Capture expiration window** in (zie hieronder) om te zorgen dat de autorisatie niet stilzwijgend verloopt.

### Capture-verloopvenster

Het capture-verloopvenster beperkt hoe lang een autorisatie open kan blijven. Wanneer het venster sluit, geeft Mollie de autorisatie automatisch vrij en kan de order niet meer worden gecaptured.

Het veld voor het verloopvenster verschijnt in de Klarna-instellingen wanneer **Manual capture** is geselecteerd. Het wordt weergegeven als een aangepast veld dat ook het door Mollie opgelegde maximum voor deze methode toont - stel geen waarde in die het getoonde maximum overschrijdt.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Klarna** uit
2. Voer een waarde in bij **Capture expiration window**
3. Klik op **Save Config** en leeg de cache

**Belangrijk:** Zodra een autorisatie verloopt, kan de order niet worden hersteld. Annuleer de Magento-order en vraag de klant een nieuwe order te plaatsen.

### Automatische capture met vertraging

Als je wilt dat Klarna-betalingen automatisch worden gecaptured maar met een kort reviewvenster, gebruik dan **Autocapture** met een vertraging.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Klarna** uit
2. Zet **Capture method** op **Autocapture**
3. Voer een waarde in bij **Capture delay** en selecteer **Hours** of **Days** als eenheid
4. Klik op **Save Config** en leeg de cache

## Checkoutflow

Klarna gebruikt een op redirect gebaseerde checkout. Nadat de klant Klarna heeft geselecteerd bij de betaalstap en de order heeft geplaatst, wordt hij doorgestuurd naar een door Klarna gehoste pagina waar hij zich verifieert en de betaling bevestigt. Na het voltooien van de Klarna-flow wordt hij teruggestuurd naar de successpagina van je winkel.

De extensie stuurt de volledige orderregelspecificatie (producten, verzending, kortingen, belastingen) naar Klarna bij het aanmaken van de transactie. Klarna gebruikt deze regels om een gespecificeerd overzicht aan de klant te tonen en om het ordertotaal te valideren. Zorg dat je belasting- en kortingsconfiguratie nauwkeurige regeltotalen oplevert, want afwijkingen zorgen ervoor dat de transactie mislukt.

## Een Klarna-order captureen (On Shipment)

Wanneer **When to capture?** is ingesteld op **On shipment**, maak je een zending aan in Magento Admin om de capture te activeren.

1. Ga naar **Sales → Orders** en open de Klarna-order
2. Klik op **Ship**
3. Vul de zendingsgegevens in (vervoerder, trackingnummer, te verzenden items)
4. Klik op **Submit Shipment**

De extensie stuurt het capture-verzoek automatisch naar Mollie wanneer de zending wordt opgeslagen. Tegelijkertijd wordt een factuur aangemaakt voor de verzonden items. Gedeeltelijke zendingen worden ondersteund - je kunt items in meerdere batches verzenden en captureen totdat de volledige order is gecaptured.

## Een Klarna-order captureen (On Invoice)

Wanneer **When to capture?** is ingesteld op **On invoice**, maak je handmatig een factuur aan om de capture te activeren.

1. Ga naar **Sales → Orders** en open de Klarna-order
2. Klik op **Invoice**
3. Pas aantallen aan als je een gedeeltelijke factuur aanmaakt
4. Klik op **Submit Invoice**

De extensie stuurt het capture-verzoek naar Mollie wanneer de factuur wordt ingediend.

## Terugbetalingen

Klarna ondersteunt zowel volledige als gedeeltelijke terugbetalingen. Terugbetalingen worden gestart vanuit het creditnota-scherm in Magento Admin en automatisch naar Mollie gestuurd.

1. Ga naar **Sales → Orders** en open de Klarna-order
2. Open het tabblad **Invoices**, klik op de factuur en klik daarna op **Credit Memo**
3. Pas aantallen of bedragen aan naar behoefte
4. Klik op **Refund** - gebruik niet **Refund Offline**, want dat slaat de Mollie API-aanroep over

De terugbetaling wordt door Klarna verwerkt op hun standaard tijdlijn. Klarna-terugbetalingen zijn geldig tot drie jaar na de oorspronkelijke transactiedatum.

## Factuur-e-mail

Wanneer een Klarna-order wordt gecaptured, stuurt Klarna zijn eigen betalingsbevestiging en factuur direct naar de klant. Om te voorkomen dat een dubbele factuur-e-mail vanuit Magento wordt verzonden, biedt de extensie een aparte schakelaar voor Klarna-orders.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Bevestig dat **Send Invoice Email** is ingesteld op **Yes** (of **No** als je nooit factuur-e-mails wilt)
3. Zet **Send Invoice Email For Klarna Orders** op **Yes** om ook de Magento-factuur-e-mail voor Klarna-orders te sturen, of op **No** om deze te onderdrukken (aanbevolen als Klarna's eigen e-mail voldoende is)
4. Klik op **Save Config** en leeg de cache

## Land- en valutabeperkingen

Klarna is beschikbaar in de volgende landen: Oostenrijk, België, Tsjechië, Denemarken, Finland, Frankrijk, Duitsland, Griekenland, Hongarije, Ierland, Italië, Nederland, Noorwegen, Polen, Portugal, Roemenië, Slowakije, Spanje, Zweden, Zwitserland en het Verenigd Koninkrijk.

Geaccepteerde valuta's zijn afhankelijk van het factuurland van de klant:

| Land | Valuta |
|---|---|
| Oostenrijk, België, Finland, Frankrijk, Duitsland, Griekenland, Hongarije, Ierland, Italië, Nederland, Portugal, Roemenië, Slowakije, Spanje | EUR |
| Tsjechië | CZK |
| Denemarken | DKK |
| Noorwegen | NOK |
| Polen | PLN |
| Zweden | SEK |
| Zwitserland | CHF |
| Verenigd Koninkrijk | GBP |

Om Klarna te beperken tot een subset van ondersteunde landen in je winkel:

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Klarna** uit
2. Zet **Payment from Applicable Countries** op **Specific Countries**
3. Selecteer de landen bij **Payment from Specific Countries**
4. Klik op **Save Config** en leeg de cache

Als alternatief kun je de Methods API inschakelen onder **Stores → Configuration → Mollie → Developer Settings → Advanced**. Deze bevraagt Mollie in realtime en filtert elke methode eruit die niet beschikbaar is voor het land en het winkelwagentotaal van de klant, inclusief Klarna.

## Ordertotaallimieten

Klarna heeft per land minimum- en maximumtransactiebedragen die door Mollie worden gehandhaafd. Je kunt aanvullende limieten instellen in Magento om te voorkomen dat Klarna verschijnt voor orders buiten een gekozen bereik.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Klarna** uit
2. Voer een waarde in bij **Minimum Order Total** en/of **Maximum Order Total**
3. Klik op **Save Config** en leeg de cache

## Betalingstoeslag

Een toeslag kan worden toegevoegd aan Klarna-orders om verwerkingskosten te compenseren. Zie [Payment Fee](PAYMENT_FEE.md) voor configuratiedetails.

## Volgende stappen

- [Order Management](ORDER_MANAGEMENT.md) - Capture-modi, facturering en terugbetalingsgedrag voor alle methoden
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan betaalmethoden
- [Configuration](CONFIGURATION.md) - Algemene instellingen inclusief de Methods API
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met orderstatus en capture-fouten
