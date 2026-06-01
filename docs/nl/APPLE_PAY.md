# Apple Pay Betalingen

Deze pagina beschrijft hoe je Apple Pay configureert in Mollie Payments voor Magento 2, inclusief de keuze tussen een externe redirect en een directe native integratie met knoppen op de productpagina en minicart.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd en een API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)
- De winkel wordt geserveerd via HTTPS - Apple Pay weigert te laden op gewone HTTP-verbindingen
- Een live API-sleutel is ingevoerd in de Mollie-configuratie, ook als de winkel in testmodus draait - zie [API Keys](API_KEYS.md)

## Apple Pay inschakelen

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Klap **Apple Pay** uit
3. Zet **Enabled** op **Yes**
4. Klik op **Save Config** en leeg de cache

**Belangrijk:** Apple Pay wordt alleen getoond aan klanten van wie het apparaat en de browser het ondersteunen en die Apple Pay hebben ingesteld. Op alle andere apparaten wordt de methode automatisch verborgen - er wordt geen foutmelding getoond aan de klant.

## Integratietype

De instelling **Integration type** bepaalt hoe het Apple Pay-betalingsvenster wordt geactiveerd.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Apple Pay** uit
2. Zet **Integration type** op een van de volgende opties:
   - **External** (standaard) - de klant selecteert Apple Pay bij de standaard checkout en wordt doorgestuurd naar een door Mollie gehoste pagina waar het Apple Pay-venster opent. Er is geen aanvullende configuratie vereist.
   - **Direct** - het Apple Pay-venster opent zonder je winkel te verlaten. Een native Apple Pay-knop verschijnt bij de checkout, en optioneel op de productdetailpagina en in de minicart.
3. Klik op **Save Config** en leeg de cache

**Belangrijk:** Directe integratie vereist merchant validation, waarbij de live API-sleutel wordt gebruikt om te verifiëren bij Apple Pay's servers. De live API-sleutel moet aanwezig zijn, ook als de winkel in testmodus staat. Als de live API-sleutel ontbreekt, mislukt de merchant validation en opent het betalingsvenster niet.

## Directe integratie

Wanneer **Integration type** is ingesteld op **Direct**, kun je Apple Pay-knoppen buiten de standaard checkoutflow plaatsen. Het domeinvalidatiebestand (`/.well-known/apple-developer-merchantid-domain-association`) wordt automatisch geserveerd door de extensie - handmatige bestandsplaatsing is niet nodig.

### Knop op de productpagina

Door deze knop in te schakelen kunnen klanten een Apple Pay-sessie starten direct vanaf een productdetailpagina, zonder de winkelwagen te gebruiken.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Apple Pay** uit
2. Zet **Enable Button on Product Page** op **Yes**
3. Zet **Buy Now Button Style** op een van de volgende opties:
   - **Black** (standaard) - gebruik op lichte achtergronden
   - **White** - gebruik op donkere of gekleurde achtergronden
   - **White Outline** - gebruik op witte of lichte achtergronden die onvoldoende contrast bieden met de effen witte stijl
4. Zet **Buy Now Button Type** op het label dat in de knop wordt getoond. Beschikbare opties: **Buy**, **Donate**, **Plain**, **Book**, **Check out** (standaard), **Subscribe**, **Add money**, **Contribute**, **Order**, **Reload**, **Rent**, **Support**, **Tip**, **Top up**, **None**
5. Klik op **Save Config** en leeg de cache

### Knop in de minicart

Door deze knop in te schakelen wordt een Apple Pay-knop in de minicart geplaatst, zodat klanten direct vanuit elke plek in de winkel kunnen betalen zonder naar de checkout te navigeren.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Apple Pay** uit
2. Zet **Enable Button in minicart** op **Yes**
3. Zet **Minicart Button Style** op **Black**, **White** of **White Outline** (dezelfde richtlijnen als hierboven)
4. Zet **Minicart Button Type** op het gewenste knoplabel (dezelfde opties als de productpagina-knop; standaard: **Check out**)
5. Klik op **Save Config** en leeg de cache

### Ondersteunde kaartnetwerken

Het Apple Pay-venster toont de kaartnetwerken die de winkel accepteert. De extensie voegt altijd Amex, Mastercard en Visa toe. Maestro en V Pay worden automatisch toegevoegd wanneer de **Capture method** van de Credit Card-betaalmethode is ingesteld op **Autocapture** - zie [Order Management](ORDER_MANAGEMENT.md).

## Capture-modus

Apple Pay ondersteunt zowel automatische als handmatige capture.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Apple Pay** uit
2. Zet **Capture method** op **Autocapture** of **Manual capture**
3. Klik op **Save Config** en leeg de cache

Voor handmatige capture configureer je **When to capture?** op **On invoice** of **On shipment**, en stel je optioneel een **Capture expiration window** in. Voor automatische capture kun je een **Capture delay** instellen (in uren of dagen) om een reviewvenster in te voegen voordat de betaling wordt afgerond. Volledige details staan in [Order Management](ORDER_MANAGEMENT.md).

## Land- en ordertotaalbeperkingen

Beperk Apple Pay tot specifieke landen of orderbedragen.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Apple Pay** uit
2. Om per land te beperken, zet **Payment from Applicable Countries** op **Specific Countries** en selecteer de toegestane landen bij **Payment from Specific Countries**
3. Om per orderbedrag te beperken, voer waarden in bij **Minimum Order Total** en/of **Maximum Order Total**
4. Klik op **Save Config** en leeg de cache

## Betalingstoeslag

Een toeslag kan worden toegevoegd aan Apple Pay-orders om verwerkingskosten te compenseren. Zie [Payment Fee](PAYMENT_FEE.md) voor configuratiedetails.

## Domeinvalidatie

Apple vereist dat elk domein dat een Apple Pay-knop toont, een domeinvalidatiebestand beschikbaar stelt op `/.well-known/apple-developer-merchantid-domain-association`. De extensie serveert dit bestand automatisch door het op te halen bij Mollie en het een week te cachen - geen handmatige stap vereist.

Om te controleren of het bestand toegankelijk is en overeenkomt met de verwachte inhoud, voer je de ingebouwde self-test uit.

1. Ga naar **Stores → Configuration → Mollie → General**
2. Klap **Mollie Configuration** uit
3. Klik op **Run Self-test**

De self-test controleert of het bestand bereikbaar is op het domein van je winkel en of de inhoud overeenkomt met het huidige Mollie-certificaat. Als de controle mislukt, controleer dan of de winkel-URL publiek toegankelijk is en of geen serverregel (WAF, botbeveiliging, onderhoudsmodus) verzoeken naar het pad `/.well-known/` blokkeert.

## Volgende stappen

- [API Keys](API_KEYS.md) - Het invoeren en beheren van je live en test API-sleutels
- [Configuration](CONFIGURATION.md) - Algemene instellingen
- [Order Management](ORDER_MANAGEMENT.md) - Capture-modi, facturering en terugbetalingen
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan betaalmethoden
- [Best Practices](BEST_PRACTICES.md) - Aanbevolen productie-instellingen inclusief de self-test
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen
