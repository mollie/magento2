# Orderbeheer

Dit artikel beschrijft hoe Mollie Payments voor Magento 2 omgaat met orderstatussen, facturering, betalingsinning, terugbetalingen en het herstellen van openstaande orders.

## Orderstatussen

De extensie kent twee configureerbare statussen toe aan elke Mollie-order: een status op het moment dat de order wordt aangemaakt en de klant wordt doorgestuurd naar de betaalpagina, en een status nadat een succesvolle betaling is bevestigd.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Status Pending** in op de status die wordt gebruikt terwijl op betaling wordt gewacht (standaard: `pending_payment`)
3. Stel **Status Processing** in op de status die wordt gebruikt na een succesvolle betaling (standaard: `processing`)
4. Klik op **Save Config** en leeg de cache

Bankoverschrijving-orders blijven in de openstaande status totdat Mollie bevestigt dat de overboeking is ontvangen, wat enkele werkdagen kan duren. Configureer een aparte status voor deze orders (bijvoorbeeld `pending_banktransfer`) om ze te onderscheiden van andere onverwerkte orders in het orderoverzicht - zie [Best Practices](BEST_PRACTICES.md) voor meer informatie.

## Aanmaken van facturen

De extensie maakt automatisch een Magento-factuur aan wanneer bevestiging van een succesvolle betaling wordt ontvangen. Schakel dit alleen uit als facturen worden beheerd door een extern systeem zoals een ERP.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Create invoice on successful payment** in op **Yes** (standaard) of **No**
3. Klik op **Save Config** en leeg de cache

### Factuur e-mail

Wanneer een factuur wordt aangemaakt, kan de extensie automatisch de factuur-e-mail naar de klant sturen. Klarna-orders hebben een aparte schakelaar zodat je Magento's factuur-e-mail kunt onderdrukken wanneer Klarna zijn eigen factuurcommunicatie verstuurt.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Send Invoice Email** in op **Yes** of **No**
3. Stel **Send Invoice Email For Klarna Orders** in op **Yes** of **No**
4. Klik op **Save Config** en leeg de cache

## Capture-modi

Sommige betaalmethoden ondersteunen een keuze tussen automatische en handmatige capture. Bij automatische capture wordt de betaling verrekend op het moment dat de klant deze voltooit. Bij handmatige capture wordt de betaling geautoriseerd maar worden er geen gelden overgemaakt totdat je de capture vanuit Magento Admin start - handig wanneer je orders wilt controleren, aanpassen of annuleren voordat de klant wordt belast.

Capture-instellingen worden per betaalmethode geconfigureerd. Methoden met een vaste capture-modus (zoals Riverty, dat altijd handmatige capture gebruikt) tonen de capture-instellingen niet.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Open de betaalmethode die je wilt configureren (bijvoorbeeld **Credit Card**)
3. Stel **Capture method** in op **Autocapture** of **Manual capture**
4. Klik op **Save Config** en leeg de cache

### Handmatige capture

Wanneer **Manual capture** is geselecteerd, moet je ook configureren wanneer de capture wordt gestart.

1. Stel **When to capture?** in op **On invoice** of **On shipment**:
   - **On invoice** - de capture wordt naar Mollie gestuurd wanneer je een factuur aanmaakt voor de order in Magento Admin
   - **On shipment** - de capture wordt verstuurd wanneer je een verzending aanmaakt; de extensie genereert automatisch eerst de factuur voor de verzonden artikelen en verricht daarna de capture
2. Stel optioneel een **Capture expiration window** in om te beperken hoe lang een autorisatie open kan blijven

Voor Klarna en Billie is de standaard **On shipment**, wat overeenkomt met de koop-nu-betaal-later-flow waarbij de klant alleen wordt belast zodra de goederen zijn verzonden. Als je geen verzending aanmaakt in Magento, wordt de capture nooit gestart.

**Belangrijk:** Een autorisatie die niet wordt gecaptured voordat het vervaltijdvenster sluit, wordt automatisch vrijgegeven. Eenmaal vrijgegeven kan de order niet meer worden gecaptured en moet deze worden geannuleerd.

### Vertraging bij automatische capture

Voor methoden die **Autocapture** gebruiken, kun je een vertraging invoegen tussen autorisatie en capture. Dit geeft je een venster om orders te controleren of te annuleren voordat de klant wordt belast.

1. Stel **Capture method** in op **Autocapture**
2. Voer een waarde in bij **Capture delay** en selecteer **Hours** of **Days** als eenheid
3. Klik op **Save Config** en leeg de cache

## Terugbetalingen

Het aanmaken van een creditnota in Magento Admin voor een order die via Mollie is betaald, stuurt automatisch een terugbetalingsverzoek naar de Mollie API. De klant ontvangt de terugbetaling binnen een termijn die wordt bepaald door zijn betaalmethode.

1. Ga naar **Sales → Orders** en open de order
2. Open het tabblad **Invoices**, klik op de factuur en klik vervolgens op **Credit Memo**
3. Pas hoeveelheden of bedragen aan indien nodig
4. Klik op **Refund** - gebruik **Refund Offline** niet, want dat slaat de API-aanroep naar Mollie over

Het Mollie-terugbetalings-ID wordt opgeslagen bij de creditnota als referentie.

Als een terugbetaling rechtstreeks in het Mollie Dashboard wordt gestart - bijvoorbeeld door een supportmedewerker - detecteert de extensie dit bij de volgende webhook-aanroep en maakt automatisch de bijbehorende creditnota aan in Magento, zonder een tweede API-aanroep te starten.

**Opmerking over cadeaukaartorders:** Als de klant een deel van de order heeft betaald met een cadeaukaart, kan alleen het gedeelte dat via Mollie is betaald via dit mechanisme worden terugbetaald. De extensie beperkt het terugbetalingsbedrag automatisch tot het via Mollie betaalde gedeelte.

## Afhandeling van mislukte betalingen

### Doorsturen na een mislukte betaling

Wanneer een betaling wordt geannuleerd of mislukt (bijvoorbeeld een geweigerde kaart of onvoldoende saldo), configureer je waarnaar de klant wordt doorgestuurd.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Redirect user when redirect fails** in op een van de volgende opties:
   - **Redirect to cart** (standaard) - stuurt de klant terug naar zijn winkelwagen
   - **Redirect to checkout (shipping)** - keert terug naar de verzendstap
   - **Redirect to checkout (payment)** - keert terug naar de stap voor betaalmethode-selectie
3. Klik op **Save Config** en leeg de cache

Foutmeldingen zoals "Betaling is geannuleerd" worden mogelijk niet op alle checkout-implementaties weergegeven. Test de foutafhandeling na het wijzigen van deze instelling.

### Order annuleren bij terugkeer naar checkout

Wanneer een klant de browserknop Vorige gebruikt terwijl een betaling nog in behandeling is, kan de extensie de order automatisch annuleren en de winkelwagen herstellen. Dit geeft direct gereserveerde voorraad vrij.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Cancel order on checkout return** in op **Yes**
3. Klik op **Save Config** en leeg de cache

**Belangrijk:** Schakel dit alleen in wanneer frequente voorraadtekorten dit noodzakelijk maken. Als een klant even wegnavigiert om orderdetails te controleren en vervolgens terugkeert om de betaling te voltooien, annuleert deze instelling zijn order.

### Order annuleren bij verbindingsfout

Als de Mollie-transactie niet kan worden aangemaakt vanwege een verbindingsfout of een gegevensvalidatiefout, kan de extensie de zojuist aangemaakte Magento-order automatisch annuleren in plaats van deze in een onoplosbare openstaande staat te laten.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Cancel order when connection fails** in op **Yes**
3. Klik op **Save Config** en leeg de cache

## Herstel van openstaande orders

De cron job voor openstaande orders controleert periodiek orders die nog in een openstaande staat in Magento staan en raadpleegt de Mollie API voor hun huidige betalingsstatus. Dit herstelt orders waarbij de betaling is geslaagd maar de webhook de winkel niet heeft bereikt - bijvoorbeeld vanwege een tijdelijke netwerkstoring of een verkeerd geconfigureerde firewallregel.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Stel **Enable Pending Orders Cron Job** in op **Yes**
3. Stel **Pending Orders Cron Job Batch Size** in - de standaard van `25` is geschikt voor de meeste winkels; verlaag dit als de cron-resources beperkt zijn
4. Klik op **Save Config** en leeg de cache

De cron job vereist dat de cron scheduler van Magento actief is. Controleer of de scheduler actief is voordat je hierop vertrouwt als herstelmechanisme. Webhooks zijn het primaire updatepad; de cron job is alleen een terugvaloptie.

## Volgende stappen

- [Configuration](CONFIGURATION.md) - Alle algemene instellingen
- [Payment Methods](PAYMENT_METHODS.md) - Instellingen per methode, inclusief capture-configuratie
- [Klarna](KLARNA.md) - Klarna-specifieke capture- en factureringsfunctionaliteit
- [Best Practices](BEST_PRACTICES.md) - Aanbevolen productie-instellingen voor orderbeheer
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met orderstatussen
