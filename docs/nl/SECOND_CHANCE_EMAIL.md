# Second Chance Email

Dit artikel legt uit hoe je de Second Chance Email-functie van Mollie Payments voor Magento 2 configureert en gebruikt. Deze functie stuurt een betalingsherinnering naar klanten die een betaling zijn begonnen maar niet hebben voltooid.

## Second Chance Email inschakelen

Het inschakelen van de functie voegt een knop **Send Payment Reminder** toe aan in aanmerking komende orders in Magento Admin en ontgrendelt de onderstaande configuratieopties. Er worden pas automatisch e-mails verstuurd als je ook automatische verzending inschakelt.

1. Ga naar **Stores → Configuration → Mollie → Second Chance Email**
2. Stel **Enable Second Chance Email** in op **Yes**
3. Klik op **Save Config** en leeg de cache

De knop **Send Payment Reminder** verschijnt op de orderdetailpagina voor orders in de staat `new`, `pending_payment` of `canceled`. Door erop te klikken wordt de e-mail direct verzonden en wordt de verzending geregistreerd onder **Sales → Mollie Payment Reminders → Sent**.

## Automatische verzending instellen

Wanneer automatische verzending is ingeschakeld, plaatst de extensie een order in de wachtrij voor een herinnering op het moment dat de klant naar Mollie wordt doorgestuurd. Een cron job controleert de wachtrij elke vijf minuten en verstuurt de e-mail zodra de geconfigureerde vertraging is verstreken.

1. Ga naar **Stores → Configuration → Mollie → Second Chance Email**
2. Stel **Automatically Send Second Chance Emails** in op **Yes**
3. Stel **Second Chance Email Delay** in op het aantal uren dat moet worden gewacht voor verzending (1-8 uur, standaard: **1**)
4. Klik op **Save Config** en leeg de cache

Voordat elke wachtrij-herinnering wordt verstuurd, controleert de extensie twee voorwaarden. Als een van beide mislukt, wordt het openstaande record verwijderd zonder te verzenden:

- De order heeft nog niet de status `processing` of `complete`.
- Alle artikelen in de order zijn nog te koop (op voorraad).

Bankoverschrijving-orders worden volledig uit de wachtrij uitgesloten, omdat klanten al worden gevraagd de betaling via hun bank te voltooien.

De extensie verstuurt maximaal één herinnering per order. Zodra een herinnering is verstuurd, worden de overige wachtrij-herinneringen van de klant verwijderd. Verzonden herinneringsrecords ouder dan een week worden automatisch opgeschoond.

## Gedrag van de betaallink

Elke herinneringsmail bevat een unieke betaallink. Wat er gebeurt wanneer de klant erop klikt, hangt af van de staat van de originele order:

- **Order nog openstaand**: de klant wordt doorgestuurd naar de originele Mollie-checkout-URL om dezelfde betaling te voltooien.
- **Order geannuleerd**: er wordt een nieuwe order aangemaakt op basis van de artikelen van de originele order en de klant wordt doorgestuurd naar een nieuwe Mollie-checkout.

De link bevat UTM-parameters voor analysetracking: `utm_source=second_chance_email`, `utm_medium=mollie_second_chance`, `utm_campaign=second_chance_order`.

### Betaalmethode voor herorders

Wanneer de originele order is geannuleerd en er een nieuwe order moet worden aangemaakt, configureer je welke betaalmethode de nieuwe order gebruikt.

1. Ga naar **Stores → Configuration → Mollie → Second Chance Email**
2. Stel **Payment Method To Use For Second Chance Payments** in op een van de volgende opties:
   - **Use the method of the original order** (standaard): de nieuwe order gebruikt dezelfde Mollie-betaalmethode die de klant oorspronkelijk had geselecteerd
   - Een ingeschakelde Mollie-betaalmethode: dwingt alle herorders tot die methode
3. Klik op **Save Config** en leeg de cache

## De e-mailsjabloon aanpassen

De standaardsjabloon bevat de lijst met orderartikelen, een betaalknop en een kort bericht. Pas deze aan via de standaard Magento-e-mailsjablooneditor.

1. Ga naar **Marketing → Communications → Email Templates**
2. Klik op **Add New Template**
3. Selecteer onder **Load default template** de optie **Mollie Second Chance Email** en klik op **Load Template**
4. Bewerk de sjabloon naar behoefte
5. Klik op **Save Template**
6. Ga naar **Stores → Configuration → Mollie → Second Chance Email**
7. Stel **Second Chance Email Template** in op je nieuwe sjabloon
8. Klik op **Save Config** en leeg de cache

De volgende variabelen zijn beschikbaar in de sjabloon:

| Variabele | Omschrijving |
|---|---|
| `{{var link}}` | De unieke betaal-URL |
| `{{var customer.name}}` | Volledige naam van de klant |
| `{{var customer.email}}` | E-mailadres van de klant |
| `{{var order.increment_id}}` | Ordernummer |
| `{{var order.total}}` | Ordertotaal |
| `{{var store.frontend_name}}` | Winkelnaam |

De onderwerpregel wordt ingesteld met de `@subject`-instructie bovenaan de sjabloon: `{{trans "Complete your payment from %store_name" store_name=$store.frontend_name}}`.

## BCC

Om een blind carbon copy te ontvangen van elke verzonden second chance-e-mail, voer je een of meer e-mailadressen in het BCC-veld in.

1. Ga naar **Stores → Configuration → Mollie → Second Chance Email**
2. Voer een door komma's gescheiden lijst van e-mailadressen in bij **Send BCC to** (laat leeg om uit te schakelen)
3. Klik op **Save Config** en leeg de cache

## Herinneringen bewaken en beheren

De herinneringswachtrij is toegankelijk onder **Sales → Mollie Payment Reminders**.

- **Pending**: orders die nog een herinnering moeten ontvangen. Toont het order-ID en wanneer het record is aangemaakt. Gebruik dit om orders te identificeren die binnenkort een herinnering ontvangen, of om afzonderlijke items handmatig te starten of te verwijderen.
- **Sent**: orders waarvoor al een herinnering is verstuurd. Gebruik dit om de verzending te controleren of verouderde records te verwijderen.

Beide overzichten ondersteunen massaacties om meerdere records tegelijk te verzenden of te verwijderen.

## Volgende stappen

- [Order Management](ORDER_MANAGEMENT.md): orderstatussen, afhandeling van mislukte betalingen en herstel van openstaande orders
- [Configuration](CONFIGURATION.md): alle algemene instellingen
- [Payment Methods](PAYMENT_METHODS.md): afzonderlijke betaalmethoden inschakelen en configureren
- [Best Practices](BEST_PRACTICES.md): aanbevolen productie-instellingen
