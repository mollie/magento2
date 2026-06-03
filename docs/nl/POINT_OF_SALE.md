# Point of Sale Betalingen

Point of Sale (POS) laat een klant online een bestelling plaatsen en betalen via een fysieke Mollie Terminal in je winkel of aan de balie. De bestelling wordt aangemaakt in Magento en het betalingsverzoek wordt rechtstreeks naar de door jou geselecteerde terminal gestuurd; er vindt geen doorverwijzing naar een externe betaalpagina plaats.

## Vereisten

- Mollie Payments for Magento 2 is geinstalleerd en een live API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)
- Minimaal een Mollie Terminal is geregistreerd en actief in je [Mollie Dashboard](https://www.mollie.com/dashboard) onder **Point of Sale → Terminals**
- POS-betalingen vereisen een live API-sleutel; de terminalintegratie is niet beschikbaar in testmodus

## Point of Sale inschakelen

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Vouw **Point Of Sale (POS)** uit
3. Zet **Enabled** op **Yes**
4. Klik op **Save Config** en leeg de cache

## De betaalmethode configureren

Met POS ingeschakeld verschijnen de volgende velden onder **Stores → Configuration → Mollie → Payment Methods → Point Of Sale (POS)**.

### Title en Description

1. Bewerk het veld **Title** om het label te wijzigen dat klanten bij het afrekenen zien (standaard: `Point Of Sale (POS)`)
2. Bewerk het veld **Description** om de betalingsomschrijving in te stellen die bij elke transactie naar Mollie wordt gestuurd - gebruik `{ordernumber}` om het Magento-ordernummer automatisch op te nemen

### Beperken tot klantengroepen

POS is bedoeld voor situaties waarbij medewerkers helpen of waarbij betalingen in de winkel plaatsvinden, en wordt daarom doorgaans beperkt tot specifieke klantengroepen in plaats van voor alle bezoekers zichtbaar te zijn.

1. Stel **Payment from Applicable Customer Groups** in op de groepen die de POS-optie bij het afrekenen mogen zien
2. Laat het veld leeg om POS voor alle klanten te verbergen (handig als je POS-bestellingen uitsluitend aanmaakt vanuit Magento Admin)

Klantengroepfiltering wordt zowel bij het afrekenen als via de REST API afgedwongen. Een klant die niet tot een toegestane groep behoort, ziet de betaalmethode niet, en het afrekenen blokkeert de bestelling als de methode via een API-aanroep wordt geselecteerd.

### Beperkingen op land en ordertotaal

1. Stel **Payment from Applicable Countries** in op **All Allowed Countries** of **Specific Countries**
2. Kies bij **Specific Countries** de toegestane landen in **Payment from Specific Countries**
3. Voer waarden in bij **Minimum Order Total** en/of **Maximum Order Total** om de orderbedragen te beperken waarvoor POS beschikbaar is
4. Klik op **Save Config** en leeg de cache

### Capture methode

De capture methode bepaalt wanneer het bedrag wordt afgeschreven nadat de klant op de terminal heeft betaald.

1. Stel **Capture method** in op **Autocapture** of **Manual capture**
   - **Autocapture** - de betaling wordt direct vastgelegd zodra de klant bevestigt op de terminal
   - **Manual capture** - de betaling wordt geautoriseerd en je triggert de capture later vanuit Magento Admin, bij het aanmaken van een factuur of bij verzending
2. Klik op **Save Config** en leeg de cache

Voor de meeste POS-scenario's is **Autocapture** de juiste keuze, omdat de klant fysiek aanwezig is en de goederen direct worden meegegeven.

## Terminalselectie bij het afrekenen

Wanneer een klant bij het afrekenen voor Point of Sale kiest, haalt de extensie de lijst met actieve terminals op uit de Mollie API en toont deze als een lijst met keuzerondjes. De terminallijst toont het merk, het model, het serienummer en de omschrijving van elk apparaat.

De klant (of de medewerker die helpt) selecteert de te gebruiken terminal voordat de bestelling wordt geplaatst. De extensie slaat de laatst gebruikte terminal op in de lokale opslag van de browser en selecteert deze vooraf bij het volgende bezoek, zodat terugkerende klanten niet opnieuw hoeven te kiezen als er slechts een terminal in gebruik is.

Als er slechts een terminal actief is op je Mollie-account, wordt deze automatisch geselecteerd en wordt er geen keuze aangeboden.

**Belangrijk:** Er moet een terminal geselecteerd zijn voordat de bestelling kan worden geplaatst. Als er geen terminal is geselecteerd, blijft de knop Place Order uitgeschakeld.

## POS-bestellingen aanmaken vanuit Magento Admin

POS-bestellingen kunnen ook rechtstreeks in Magento Admin worden aangemaakt, bijvoorbeeld wanneer medewerkers telefonisch bestellingen opnemen en de klant persoonlijk komt betalen.

1. Ga naar **Sales → Orders** en klik op **Create New Order**
2. Selecteer de klant en de winkel, voeg producten toe en vul het adres in
3. Selecteer in het gedeelte **Payment Method** de optie **Point Of Sale (POS)**
4. Selecteer de terminal uit de lijst met actieve apparaten
5. Klik op **Submit Order**

De extensie plaatst de bestelling en stuurt het betalingsverzoek direct naar de geselecteerde terminal. Het terminalscherm activeert zich en vraagt de klant hun kaart of apparaat aan te bieden.

## Betalingsbevestigingsstroom

Nadat de bestelling is geplaatst, krijgt de klant een wachtpagina te zien die de Mollie API pollt voor de betaalstatus. De pagina toont de actuele status en werkt automatisch bij.

- Zolang de betaling in behandeling is, toont de pagina de huidige status (bijvoorbeeld `pending` of `authorized`)
- Wanneer de betaling is bevestigd, wordt de klant doorgestuurd naar de standaard orderbevestigingspagina
- Als de betaling op de terminal wordt geannuleerd, verschijnt een knop **Retry** zodat de klant de betaling opnieuw kan proberen via dezelfde of een andere terminal, zonder de bestelgegevens opnieuw in te voeren

De retry-stroom herstelt de winkelwagen van de klant en stuurt hem terug naar de betalingsstap van het afrekenen.

## Orderbeheer

### Facturering en capture

POS-bestellingen volgen dezelfde factureringsregels als andere Mollie-betaalmethoden. Met **Autocapture** maakt de extensie automatisch de Magento-factuur aan zodra het webhook voor betalingsbevestiging is ontvangen.

Met **Manual capture**:

1. Ga naar **Sales → Orders** en open de POS-bestelling
2. Klik op **Invoice**
3. Controleer de factuur en klik op **Submit Invoice** - dit triggert het capture-verzoek naar Mollie
4. Het bedrag wordt afgeschreven en de factuur wordt als betaald gemarkeerd

### Terugbetalingen

Terugbetalingen voor POS-bestellingen worden verwerkt via de standaard aanmaak van creditnota's in Magento.

1. Ga naar **Sales → Orders** en open de bestelling
2. Open het tabblad **Invoices**, klik op de factuur en klik vervolgens op **Credit Memo**
3. Pas hoeveelheden of bedragen aan waar nodig
4. Klik op **Refund** - gebruik niet **Refund Offline**, want dat slaat de API-aanroep naar Mollie over

De terugbetaling wordt naar de Mollie API gestuurd en gecrediteerd op de kaart of het account dat de klant op de terminal heeft gebruikt. De doorlooptijd voor het verschijnen van de terugbetaling is afhankelijk van de bank van de klant.

Gedeeltelijke terugbetalingen per factuurregels worden ondersteund.

## Beperkingen

- POS-betalingen vereisen een live API-sleutel. Testmodus wordt niet ondersteund omdat terminals fysieke apparaten zijn die niet gesimuleerd kunnen worden.
- De terminallijst wordt live opgehaald van de Mollie API bij het afrekenen. Als de Mollie API niet bereikbaar is, worden er geen terminals geretourneerd en kan de POS-methode niet worden gebruikt.
- POS is niet compatibel met headless of GraphQL-only checkouts die geen terminalselectie implementeren. Het veld `mollie_available_terminals` is beschikbaar op het GraphQL-type `AvailablePaymentMethod` voor eigen implementaties - zie [Headless](HEADLESS.md).
- Kaarten opslaan en abonnementsbetalingen worden niet ondersteund voor POS-transacties.
- Apple Pay en Google Pay op de terminal worden beheerd door de firmware van de terminal en het apparaat van de klant. Deze zijn niet afzonderlijk configureerbaar in Magento.

## Volgende stappen

- [Order Management](ORDER_MANAGEMENT.md) - Capture-modi, facturering en terugbetalingsgedrag
- [API Keys](API_KEYS.md) - Wisselen tussen test- en livemodus
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan POS-betalingen
- [Headless](HEADLESS.md) - Terminalselectie in headless checkouts
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met betaalmethoden
