# Google Pay Betalingen

Deze pagina beschrijft hoe je de Google Pay-betaalmethode configureert in Mollie Payments voor Magento 2.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd en een API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)
- Google Pay moet zijn ingeschakeld in je Mollie-account (via het Mollie Dashboard onder **Payment methods**)

## Google Pay inschakelen

Google Pay is standaard uitgeschakeld. Anders dan de meeste Mollie-betaalmethoden wordt het niet beheerd door de Methods API, dus het wordt niet automatisch ingeschakeld wanneer je het activeert in het Mollie Dashboard. Je moet het handmatig inschakelen in Magento Admin.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Klap **Google Pay** uit
3. Zet **Enabled** op **Yes**
4. Klik op **Save Config** en leeg de cache - betaalmethoden zijn niet zichtbaar voor klanten totdat de cache is geleegd

**Belangrijk:** Google Pay wordt alleen getoond bij de checkout wanneer de browser van de klant het ondersteunt en zij een betaalmethode hebben opgeslagen in Google Pay. Browsers die de Google Payment Request API niet ondersteunen, tonen deze optie niet, ongeacht de configuratie.

## Titel

Het veld **Title** stelt het label in dat klanten zien bij de checkout. De standaardwaarde is `Google Pay`.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Google Pay** uit
2. Pas het veld **Title** aan
3. Klik op **Save Config** en leeg de cache

## Capture-modus

Google Pay gebruikt altijd automatische capture. Het veld **Capture method** is alleen-lezen en kan niet worden omgeschakeld naar handmatige capture.

### Capture-vertraging

Je kunt een vertraging instellen tussen autorisatie en afrekening om jezelf een venster te geven om orders te beoordelen of te annuleren voordat de klant wordt belast.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Google Pay** uit
2. Voer een getal in bij **Capture delay**
3. Zet **Capture delay unit** op **Hours** of **Days**
4. Klik op **Save Config** en leeg de cache

Laat **Capture delay** leeg om de betaling direct bij autorisatie af te rekenen.

## Land- en ordertotaalbeperkingen

Beperk Google Pay tot specifieke landen of orderbedragen.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Google Pay** uit
2. Om per land te beperken, zet **Payment from Applicable Countries** op **Specific Countries** en selecteer de toegestane landen bij **Payment from Specific Countries**
3. Om per orderbedrag te beperken, voer waarden in bij **Minimum Order Total** en/of **Maximum Order Total**
4. Klik op **Save Config** en leeg de cache

## Betalingstoeslag

Een toeslag kan worden toegevoegd aan Google Pay-orders om verwerkingskosten te compenseren. Zie [Payment Fee](PAYMENT_FEE.md) voor configuratiedetails.

## Sorteervolgorde

Het veld **Sort Order** bepaalt de positie van Google Pay ten opzichte van andere betaalmethoden bij de checkout. Lagere nummers verschijnen eerst.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Google Pay** uit
2. Voer een getal in bij **Sort Order**
3. Klik op **Save Config** en leeg de cache

## Volgende stappen

- [Payment Methods](PAYMENT_METHODS.md) - Overzicht van alle beschikbare betaalmethoden
- [Order Management](ORDER_MANAGEMENT.md) - Capture-, facturerings- en terugbetalingsgedrag
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan betaalmethoden
- [Configuration](CONFIGURATION.md) - Algemene instellingen inclusief de Methods API
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met betaalmethoden
