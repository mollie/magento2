# Betalingstoeslag

Dit artikel legt uit hoe je een betalingstoeslag (payment fee) per betaalmethode configureert, belastingafhandeling instelt, en hoe toeslagen worden weergegeven in ordertotalen, facturen en creditnota's.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd en ten minste één betaalmethode is ingeschakeld - zie [Installation](INSTALLATION.md) en [Payment Methods](PAYMENT_METHODS.md)
- De betaalmethode waaraan je een toeslag wilt toevoegen, moet actief zijn in **Stores → Configuration → Mollie → Payment Methods**

## Wat de betalingstoeslag doet

De betalingstoeslag voegt een toeslag toe aan het ordertotaal wanneer een klant bij de checkout een specifieke Mollie-betaalmethode selecteert. De toeslag verschijnt als een afzonderlijke regelpost met het label **Payment Fee** in de winkelwagen, orderoverzicht, factuur en creditnota. De toeslag wordt per betaalmethode geconfigureerd, zodat je verschillende bedragen of percentages kunt rekenen voor verschillende methoden, of de toeslag volledig kunt uitschakelen voor methoden waarbij je de kosten zelf wilt dragen.

## Een toeslag configureren voor een betaalmethode

Elke actieve Mollie-betaalmethode heeft zijn eigen toeslagsinstellingen. In de onderstaande stappen wordt iDEAL als voorbeeld gebruikt, maar dezelfde velden zijn aanwezig voor elke ondersteunde methode.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Open de betaalmethode die je wilt configureren (bijvoorbeeld **iDEAL**)
3. Stel **Payment Surcharge** in op een van de volgende opties:
   - **No** - er wordt geen toeslag toegevoegd (standaard)
   - **Fixed Fee** - een vast bedrag wordt toegevoegd aan elke order
   - **Percentage** - een percentage van het ordersubtotaal wordt toegevoegd
   - **Fixed Fee and Percentage** - zowel een vast bedrag als een percentage worden samen opgeteld
4. Configureer de velden die verschijnen op basis van het gekozen type (zie secties hieronder)
5. Klik op **Save Config** en leeg de cache

De toeslagvelden verschijnen alleen wanneer de betaalmethode is ingeschakeld en het toeslagtype niet **No** is.

### Vast bedrag

Een vast bedrag voegt hetzelfde bedrag toe aan elke in aanmerking komende order, ongeacht de orderwaarde.

1. Voer na het instellen van **Payment Surcharge** op **Fixed Fee** het toeslagbedrag (inclusief belasting) in bij **Payment Surcharge fixed amount**
2. Selecteer de juiste belastingklasse in **Payment Surcharge Tax Class** om ervoor te zorgen dat de toeslag correct wordt belast
3. Klik op **Save Config** en leeg de cache

Het veld voor het vaste bedrag accepteert een decimale waarde. Gebruik een punt als decimaalscheidingsteken - komma's en procenttekens worden automatisch verwijderd bij opslaan.

### Percentage

Een percentagetoeslag wordt berekend als een breuk van het ordersubtotaal.

1. Voer na het instellen van **Payment Surcharge** op **Percentage** een waarde in tussen `0` en `10` in **Payment Surcharge percentage** - voer bijvoorbeeld `1.5` in voor 1,5%
2. Voer optioneel een waarde in bij **Payment Surcharge limit** om de toeslag te beperken tot een maximumbedrag (inclusief belasting)
3. Selecteer de juiste belastingklasse in **Payment Surcharge Tax Class**
4. Klik op **Save Config** en leeg de cache

Het percentage wordt toegepast op het basissubtotaal inclusief belasting. Je kunt bepalen of verzendkosten en kortingen ook worden meegenomen in het basisbedrag - zie [Grondslag voor toeslagberekening](#grondslag-voor-toeslagberekening) hieronder.

### Vast bedrag en percentage

Dit type combineert beide benaderingen: het berekende percentage en het vaste bedrag worden opgeteld om de definitieve toeslag te bepalen.

1. Voer na het instellen van **Payment Surcharge** op **Fixed Fee and Percentage** waarden in bij zowel **Payment Surcharge fixed amount** als **Payment Surcharge percentage**
2. Voer optioneel een waarde in bij **Payment Surcharge limit** om de gecombineerde toeslag te beperken tot een maximumbedrag (inclusief belasting)
3. Selecteer de juiste belastingklasse in **Payment Surcharge Tax Class**
4. Klik op **Save Config** en leeg de cache

Wanneer een limiet is ingesteld, wordt de gecombineerde toeslag beperkt tot dat bedrag. De limiet is niet van toepassing op configuraties met alleen een vast bedrag.

## Grondslag voor toeslagberekening

Standaard wordt de percentagetoeslag berekend op het ordersubtotaal inclusief belasting. Met twee globale instellingen in de sectie **Invoicing & Surcharges** kun je aanpassen wat er in dat basisbedrag wordt opgenomen.

1. Ga naar **Stores → Configuration → Mollie → Order Management → Invoicing & Surcharges**
2. Stel **Include shipping in Surcharge calculation** in:
   - **No** (standaard) - de toeslagbasis is alleen het subtotaal
   - **Yes** - verzendkosten worden opgeteld bij het subtotaal voordat het percentage wordt berekend
3. Stel **Include discount in Surcharge calculation** in:
   - **No** (standaard) - kortingen verminderen de toeslagbasis niet
   - **Yes** - het kortingsbedrag wordt afgetrokken van het subtotaal voordat het percentage wordt berekend
4. Klik op **Save Config** en leeg de cache

Deze instellingen gelden globaal voor alle betaalmethoden die zijn geconfigureerd met een percentage- of gecombineerde toeslag. Vaste bedragen worden niet beïnvloed.

## Belastingafhandeling

De extensie past belasting toe op de betalingstoeslag via de standaard belastingberekeningsengine van Magento. Je wijst een productbelastingklasse toe aan elke toeslag via het veld **Payment Surcharge Tax Class**. De extensie zoekt vervolgens het toepasselijke belastingtarief op uit je Magento-belastingregels op basis van het adres van de klant en de geselecteerde belastingklasse.

De bedragen die je in het Admin invoert, worden behandeld als inclusief belasting. Als je bijvoorbeeld een vast bedrag van `1,21` invoert met een btw-klasse van 21%, is de toeslag die aan de klant wordt getoond `1,21` totaal: `1,00` netto plus `0,21` belasting. Het belastingdeel wordt apart vermeld en correct gerapporteerd op facturen en in de belastingrapporten van Magento.

Het belastingbedrag op de betalingstoeslag wordt opgenomen in de totale belastingregel van de order, samen met productbelastingen.

## De sorteervolgorde van het totaal aanpassen

De regel **Payment Fee** verschijnt standaard in ordertotalen op sorteerposition `25`, waardoor deze na verzending en voor het eindtotaal wordt geplaatst. Dit aanpassen:

1. Ga naar **Stores → Configuration → Sales → Sales → Checkout Totals Sort Order**
2. Wijzig de waarde van **Mollie Payment Fee** naar een andere sorteerpositie
3. Klik op **Save Config** en leeg de cache

Lagere nummers plaatsen de regel hoger in het totaalblok. De standaard van `25` plaatst de toeslag tussen verzending (`15`) en belasting (`20` standaard in de meeste installaties).

## Hoe toeslagen worden weergegeven in orders, facturen en creditnota's

Zodra een order is geplaatst, wordt de betalingstoeslag opgeslagen bij de order en automatisch doorgegeven.

**Orderweergave:** De regel **Payment Fee** verschijnt in het totaalblok van de order in Magento Admin onder **Sales → Orders → [order]**. Het toont het gecombineerde toeslag- en belastingbedrag.

**Facturen:** Wanneer een factuur voor de order wordt aangemaakt, wordt de betalingstoeslag automatisch gekopieerd naar de factuurtotalen. Deze verschijnt als een aparte regel **Payment Fee** op zowel de factuurweergave in Admin als de pdf-factuur die naar de klant wordt gestuurd.

**Creditnota's:** De betalingstoeslag wordt terugbetaald op de eerste creditnota die alle resterende artikelen dekt (een volledige terugbetaling of de laatste gedeeltelijke terugbetaling). Gedeeltelijke creditnota's voor een subset van artikelen bevatten de betalingstoeslag niet. Wanneer de toeslag wordt terugbetaald, verschijnt deze als een regel **Payment Fee** in de creditnotatotalen.

## Beperkingen en randgevallen

- De betalingstoeslag is alleen van toepassing op Mollie-betaalmethoden. Deze wordt niet toegepast op andere betaalgateways.
- De methode **Express Components** ondersteunt geen toeslagconfiguratie.
- De percentagetoeslag is beperkt tot maximaal 10% door de Admin-invoervalidatie. Als je een hogere waarde nodig hebt, vereist dit een aanpassing op codeniveau.
- Wanneer een klant bij de checkout van betaalmethode wisselt, wordt de toeslag direct opnieuw berekend. Als voor de nieuwe methode geen toeslag is geconfigureerd, verdwijnt de regel uit de winkelwagentotalen.
- De toeslag wordt opgeslagen in zowel de winkelvaluta als de basisvaluta. Valutaconversie maakt gebruik van de wisselkoers die van kracht is op het moment dat de toeslag wordt geïnd, niet op het moment van facturering.
- Gedeeltelijke creditnota's betalen de betalingstoeslag niet terug. Alleen de laatste creditnota die het terugbetaalde artikelaantal op nul brengt, bevat de toeslag-terugbetaling. Maak de laatste gedeeltelijke creditnota of een volledige creditnota aan om de toeslag-terugbetaling te starten.

## Volgende stappen

- [Payment Methods](PAYMENT_METHODS.md) - Afzonderlijke betaalmethoden inschakelen en configureren
- [Order Management](ORDER_MANAGEMENT.md) - Factuursamenstelling, terugbetalingen en creditnota's
- [Configuration](CONFIGURATION.md) - Alle algemene instellingen
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen
