# Probleemoplossing

Dit artikel behandelt de ingebouwde diagnosehulpmiddelen en oplossingen voor de meest voorkomende problemen die je kunt tegenkomen met Mollie Payments voor Magento 2.

## Ingebouwde zelftest

De zelftest voert een reeks geautomatiseerde controles uit op je installatie en rapporteert fouten en waarschuwingen direct in beeld. Voer de test uit na de eerste installatie en telkens wanneer je een configuratieprobleem vermoedt.

1. Ga naar **Stores → Configuration → Mollie → General → Mollie Configuration**
2. Klik op **Run Self-test**
3. Bekijk de resultaten in het paneel dat onder de knop verschijnt

De zelftest controleert het volgende:

- PHP-versie en de `ext-json`-extensie zijn aanwezig
- De Mollie PHP API-client is geïnstalleerd (bevestigt een op Composer gebaseerde installatie)
- Het webhook-eindpunt op `/mollie/checkout/webhook/` is publiek bereikbaar en retourneert `OK`
- Webhooks zijn ingeschakeld bij gebruik van testmodus (een veelgemaakte fout op stagingomgevingen)
- De `mollie.transaction.processor` Message Queue-consumer is geconfigureerd en mag actief zijn
- Alle vereiste extension attributes zijn aanwezig (bevestigt dat `setup:di:compile` is uitgevoerd)
- De wachtstatus voor bankoverschrijvingen is niet ingesteld op de generieke `pending_payment`-status
- Het Apple Pay-domeinvalidatiebestand is aanwezig en komt overeen met Mollie's kopie
- GeoIP-modules die webhook-routering kunnen verstoren, worden gedetecteerd
- Als het Hyvä Theme actief is, zijn de vereiste Mollie-compatibiliteitsmodules geïnstalleerd

Fouten blokkeren een correcte werking en moeten worden opgelost. Waarschuwingen wijzen op configuratiekeuzes die problemen kunnen veroorzaken, maar dit niet altijd doen.

**Belangrijk:** Voer de zelftest uit vanaf een publiek toegankelijke server. De webhook-bereikbaarheidscontrole doet een uitgaand HTTP-verzoek naar je eigen winkel, wat niet kan slagen vanaf localhost of een server achter een VPN die niet blootgesteld is aan het internet.

## Debug-logging

De extensie schrijft gedetailleerde verzoek- en antwoordgegevens naar `var/log/mollie.log`. Bij een nieuwe installatie is **Debug requests** standaard ingeschakeld - schakel dit uit op productiewinkels tenzij je het actief nodig hebt voor probleemoplossing.

### Debug-logging inschakelen

1. Ga naar **Stores → Configuration → Mollie → General → Debug & Logging**
2. Zet **Debug requests** op **Yes**
3. Klik op **Save Config**

Het is niet nodig om de cache te legen. Logging start direct voor volgende verzoeken.

### Het logboek lezen in het beheer

Wanneer logging is ingeschakeld, verschijnt een paneel **Show log** onder de instelling **Debug requests**. Het toont de meest recente 100 vermeldingen direct in de browser zonder servertoegang.

### Het onbewerkte logbestand raadplegen

Het logboek wordt geschreven naar `<magento-root>/var/log/mollie.log`. Je kunt het via de commandoregel volgen:

```bash
tail -f var/log/mollie.log
```

### Loggegevens anonimiseren

Als je loguitvoer moet delen met een derde partij, schakel dan **Anonymize debug requests** in (zichtbaar wanneer **Debug requests** op **Yes** staat). Dit vervangt persoonlijk identificeerbare waarden (namen, e-mailadressen, adressen) voordat ze naar het logboek worden geschreven.

### Het debug-pakket downloaden

Het debug-pakket bundelt het logbestand samen met geredigeerde configuratie- en omgevingsmetadata in één archief, geschikt voor het delen met Mollie-ondersteuning.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Klik op **Download debug information**

De download is een `.tar.gz`-archief. Gevoelige waarden (API-sleutels, versleutelingssleutel, wachtwoorden) worden automatisch geredigeerd.

## Veelvoorkomende problemen

### Betaalmethoden verschijnen niet bij de checkout

Betaalmethoden verschijnen niet bij de checkout wanneer de extensie niet kan bevestigen dat een methode actief en beschikbaar is voor de huidige winkelwagen.

Controleer het volgende in volgorde:

1. Bevestig dat de extensie is ingeschakeld: ga naar **Stores → Configuration → Mollie → General → Mollie Configuration** en controleer of **Enabled** op **Yes** staat
2. Bevestig dat een API-sleutel is opgeslagen en dat de modus (**Modus**) correct is ingesteld - zie [API-sleutels](API_KEYS.md)
3. Ga naar **Stores → Configuration → Mollie → Payment Methods** en bevestig dat de specifieke betaalmethode is ingeschakeld
4. Leeg de cache: ga naar **System → Cache Management** en klik op **Flush Magento Cache**. Wijzigingen in de zichtbaarheid van betaalmethoden zijn pas zichtbaar voor klanten nadat de cache is geleegd
5. Als **Enable the methods API** op **Yes** staat onder **Stores → Configuration → Mollie → Developer Settings → Advanced**, filtert de extensie methoden op basis van het land van de klant en het winkelwagentotaal. De methode is mogelijk legitiem niet beschikbaar voor de huidige winkelwagen. Schakel de Methods API tijdelijk uit om te bevestigen of dit de oorzaak is, schakel hem daarna weer in en pas de methode-instellingen van het Mollie-profiel aan in het [Mollie Dashboard](https://www.mollie.com/dashboard)
6. Controleer of de methode actief is op je Mollie-profiel in het Dashboard. Een methode die in Magento is ingeschakeld maar niet geactiveerd op het Mollie-profiel, wordt niet door de API geretourneerd

### Webhooks worden niet ontvangen

Mollie stuurt een POST-verzoek naar je webhook-URL wanneer de transactiestatus verandert. Als webhooks niet worden ontvangen, worden orders niet automatisch bijgewerkt.

1. Voer de zelftest uit (zie hierboven). De webhook-bereikbaarheidscontrole geeft aan of Mollie het eindpunt kan bereiken en welke HTTP-status het heeft geretourneerd
2. Bevestig dat de webhook-URL publiek toegankelijk is. Die moet POST-verzoeken van externe IP-adressen accepteren zonder doorverwijzingen, authenticatievereisten of firewallblokkades
3. Als de winkel achter Cloudflare staat, stel dan een bypass-regel in voor de Mollie webhook-eindpunten. Cloudflare's standaard botbeveiliging daagt geautomatiseerde POST-verzoeken uit. Zie [Cloudflare-configuratie](#cloudflare-configuratie) hieronder
4. Als een GeoIP- of winkelwisselmodule actief is, sluit `/mollie/checkout/webhook/` uit van de doorverwijzingslogica. De zelftest markeert bekende GeoIP-modules wanneer ze worden gedetecteerd
5. Als de winkel in onderhoudsmodus staat tijdens een betaling, onderschept de onderhoudspagina de webhook. Gebruik Magento's IP-toegestane lijst om webhook-verwerking toe te staan tijdens onderhoudsvensters:
   ```bash
   php bin/magento maintenance:allow-ips --none
   ```
6. Controleer of **Process transactions in the queue** is ingeschakeld onder **Stores → Configuration → Mollie → Developer Settings → Advanced**. Als dat zo is en de `mollie.transaction.processor`-consumer niet actief is, worden webhooks geaccepteerd maar orders niet bijgewerkt. Start de consumer:
   ```bash
   php bin/magento queue:consumers:start mollie.transaction.processor
   ```

### Orders vastgelopen in wachtstatus

Orders blijven in `pending_payment` wanneer de webhook niet is ontvangen of niet verwerkt kon worden.

1. Controleer eerst de webhook-probleemoplossingsstappen hierboven
2. Schakel de cron job voor openstaande orders in om vastgelopen orders automatisch te herstellen:
   - Ga naar **Stores → Configuration → Mollie → Order Management**
   - Zet **Enable Pending Orders Cron Job** op **Yes**
   - Klik op **Save Config**

   De cron job controleert alle Mollie-orders die al tussen 30 minuten en 10 dagen in `pending_payment` staan, vraagt de Mollie API naar hun huidige status en werkt ze dienovereenkomstig bij. Wanneer een order via deze cron job wordt bijgewerkt, wordt een opmerking toegevoegd aan de ordergeschiedenis dat de webhook niet is ontvangen.

3. Om een enkele vastgelopen order direct te herstellen, kun je handmatig een statuscontrole forceren. Open de order in Magento Admin - Mollie verwerkt de transactie opnieuw bij de volgende webhook, of je kunt dit activeren via het Mollie Dashboard door de webhook-melding opnieuw te verzenden via de betalingsdetailpagina.

4. Schakel debug-logging in (zie hierboven) en controleer `var/log/mollie.log` op fouten die zijn opgetreden toen de webhook werd ontvangen. Verwerkingsfouten worden gelogd met het order-ID, waardoor het eenvoudig is om een vastgelopen order te koppelen aan een logvermelding.

### API-sleutelfouten

API-sleutelfouten verschijnen wanneer de sleutel ontbreekt, een onjuist formaat heeft of niet overeenkomt met de geselecteerde modus.

- Een testsleutel begint met `test_`. Een live-sleutel begint met `live_`. Het gebruik van een live-sleutel in testmodus of andersom veroorzaakt een authenticatiefout
- De extensie valideert het sleutelformaat voor het opslaan. Als het veld **Profile ID** onder de sleutelvelden leeg is na het opslaan, is de sleutel afgewezen door de Mollie API
- Sleutels worden versleuteld opgeslagen. Als de Magento-versleutelingssleutel verandert (bijvoorbeeld na een migratie), kunnen opgeslagen sleutels onleesbaar worden. Voer de sleutels handmatig opnieuw in
- In een installatie met meerdere winkels kunnen sleutels op een ander niveau zijn ingesteld dan je verwacht. Gebruik de winkelweergaveschakelaar bovenaan de configuratiepagina om de sleutel op elk niveau te controleren - zie [API-sleutels](API_KEYS.md)
- Klik op **Test Apikey** (de knop naast de sleutelvelden) om de momenteel actieve sleutel te valideren tegen de Mollie API zonder de configuratiepagina te verlaten

### Cache-gerelateerde problemen

Verschillende soorten problemen worden opgelost na het legen van de cache:

- Betaalmethoden die zijn toegevoegd of verwijderd in Magento Admin zijn niet zichtbaar bij de checkout
- Configuratiewijzigingen (modus, API-sleutel, in-/uitgeschakelde status) treden niet in werking
- Pictogrammen van betaalmethoden worden niet bijgewerkt

Leeg de cache na elke configuratiewijziging:

```bash
php bin/magento cache:flush
```

Of via Magento Admin: ga naar **System → Cache Management** en klik op **Flush Magento Cache**.

Als problemen aanhouden na het legen, genereer dan statische content opnieuw (vereist bij het wisselen van Magento-modus of na het implementeren van wijzigingen op een productieserver):

```bash
php bin/magento setup:static-content:deploy
```

### Mollie Components laadt niet

Mollie Components rendert het creditcardformulier inline op de checkoutpagina. Als het ingesloten formulier niet verschijnt en klanten worden doorgestuurd naar de gehoste betaalpagina van Mollie, controleer dan het volgende:

1. Bevestig dat **Use Mollie Components** op **Yes** staat onder **Stores → Configuration → Mollie → Payment Methods → Credit Card**
2. Bevestig dat het veld **Profile ID** onder **Stores → Configuration → Mollie → General → Mollie Configuration** is ingevuld. Components heeft het Profile ID nodig om te initialiseren. Het Profile ID wordt automatisch ingesteld wanneer je een geldige API-sleutel opslaat - als het leeg is, sla je API-sleutel dan opnieuw op
3. Leeg de cache en herlaad de checkoutpagina in een nieuwe browsersessie (of met geleegde browsercache) om te zorgen dat de bijgewerkte configuratie wordt geladen
4. Open de browserconsole voor ontwikkelaars op de checkoutpagina en controleer op JavaScript-fouten. Een Content Security Policy (CSP) die verzoeken naar `js.mollie.com` blokkeert, voorkomt dat Components laadt. Voeg `js.mollie.com` toe aan je CSP `script-src`-richtlijn als je CSP-headers handmatig beheert

## Cloudflare-configuratie

Cloudflare's WAF Managed Rules, Bot Fight Mode, Super Bot Fight Mode en aangepaste firewallregels kunnen inkomende webhook-verzoeken van Mollie blokkeren, waardoor orders vastlopen in de wachtstatus. Pas de volgende configuratie toe om webhook-verkeer door te laten.

### 1. Haal Mollie's uitgaande IP-adressen op

Mollie publiceert de huidige uitgaande IP-reeksen. Haal ze op voordat je toegangsregels configureert:

```bash
curl https://api.mollie.com/v2/outgoing-ips
```

Sla de geretourneerde lijst op - je hebt die nodig in stappen 3 en 4.

### 2. Maak een WAF-aangepaste regel aan om webhook-paden toe te staan

Ga in je Cloudflare-dashboard naar **Security → WAF → Custom Rules** en maak een regel aan die WAF-controles omzeilt voor Mollie's webhook-eindpunten.

Overeenkomstexpressie (pas je zone aan indien nodig):

```
(http.request.uri.path contains "/mollie/checkout/webhook") or
(http.request.uri.path contains "/mollie/express/webhook") or
(http.request.uri.path contains "/mollie_subscriptions/api/webhook")
```

Stel de actie in op **Skip** en selecteer **All remaining custom rules** en **WAF Managed Rules**.

### 3. Schakel Bot Fight Mode uit voor webhook-paden

Bot Fight Mode blokkeert verzoeken die het als geautomatiseerd identificeert, waaronder Mollie's webhook-bezorging. Je hebt drie opties:

- **Globaal uitschakelen** (niet aanbevolen voor productie): schakel Bot Fight Mode volledig uit onder **Security → Bots**
- **Bot Management-overslagregel**: maak een WAF-aangepaste regel aan die overeenkomt met de webhook-paden (dezelfde expressie als stap 2) en stel de actie in op **Skip → Bot Fight Mode**
- **Cloudflare Workers**: implementeer een Worker op de webhook-paden die verzoeken doorgeeft zonder Bot Fight Mode-interferentie

### 4. Controleer IP-toegangsregels

Bevestig dat geen van Mollie's uitgaande IP-adressen (uit stap 1) zijn geblokkeerd onder **Security → WAF → Tools → IP Access Rules**. Verwijder blokkeringsregels die overlappen met Mollie's IP-reeksen.

### 5. Controleer Page Rules en Configuration Rules

Page Rules en Configuration Rules kunnen beveiligingsinstellingen overschrijven. Bekijk regels die overeenkomen met de webhook-paden en bevestig dat geen ervan botbeveiliging opnieuw inschakelt of een uitdaging activeert.

### Verificatie

Gebruik na het toepassen van de configuratie de zelftest (zie [Ingebouwde zelftest](#ingebouwde-zelftest)) om te bevestigen dat het webhook-eindpunt bereikbaar is. De test doet een uitgaand POST-verzoek naar je eigen winkel, wat mislukt als Cloudflare het verzoek nog steeds uitdaagt.

**Veelvoorkomende symptomen bij onvolledige configuratie:**

| Symptoom | Waarschijnlijke oorzaak |
|---|---|
| Orders raken af en toe vast in wachtstatus | Snelheidsbeperkende regel verstoort webhook-bezorging |
| HTTP 403 op webhook-eindpunt | WAF- of firewallregel nog actief voor dat pad |
| Webhook werkt in testmodus maar niet live | Andere Cloudflare-zone of regelset per omgeving toegepast |

---

## Moduleconflicten

Magento-modules van derden kunnen de Mollie-extensie verstoren op manieren waardoor betaalmethoden verdwijnen, webhooks stil mislukken of de checkout volledig kapot gaat. De zelftest (zie [Ingebouwde zelftest](#ingebouwde-zelftest)) markeert bekende conflicterende modules zoals GeoIP-doorverwijzers automatisch. Voor onbekende conflicten volg je de onderstaande stappen.

### Tekenen van een moduleconflict

- Betaalmethoden verdwijnen na het installeren of bijwerken van een andere module
- JavaScript-fouten in de browserconsole op de checkoutpagina
- Webhooks worden ontvangen maar orders worden niet bijgewerkt
- Aangepaste checkout-stappen of adresvalidatie mislukken wanneer Mollie-methoden zijn geselecteerd

### Schakel ontwikkelaarsmodus in voor betere foutmeldingen

Magento onderdrukt de meeste fouten in productiemodus. Schakel over naar ontwikkelaarsmodus op een stagingkopie van de winkel om de volledige uitzonderingstracering zichtbaar te maken:

```bash
php bin/magento deploy:mode:set developer
```

Reproduceer het probleem en controleer de foutuitvoer in de browser of in de logbestanden.

### Controleer de logbestanden

Twee logbestanden registreren de meeste conflicten:

```bash
tail -f var/log/exception.log
tail -f var/log/system.log
```

Zoek naar uitzonderingen die samenvallen met het probleem. De klassenaam in de tracering identificeert meestal de conflicterende module.

### Isoleer het conflict

Schakel modules van derden één voor één uit om te bepalen welke het conflict veroorzaakt:

```bash
php bin/magento module:disable Vendor_ModuleName
php bin/magento cache:flush
```

Test na elke uitschakeling. Schakel de module opnieuw in zodra je hem hebt geïdentificeerd:

```bash
php bin/magento module:enable Vendor_ModuleName
php bin/magento cache:flush
```

### Veelvoorkomende conflictbronnen

| Moduletype | Hoe het conflicteert |
|---|---|
| GeoIP / winkelwisselaars | Verwijst de webhook-URL door naar de verkeerde winkelweergave of taal |
| Aangepaste checkout-modules | Overschrijft Magento's betaalstap op een manier die Mollie's aanvullende gegevens verwijdert |
| Sessie- / cookie-modules | Verstoren de herstel van de winkelwagen na een mislukte betaling |
| CSP- / beveiligingsheader-modules | Blokkeren het laden van `js.mollie.com`, waardoor Mollie Components kapot gaat |
| Aangepaste order-observers | Worden uitgevoerd na Mollie's observer en herstellen de orderstatus of factuuraanleg |

Zodra je de conflicterende module hebt geïdentificeerd, meld het conflict aan de moduleverkoper met de uitzonderingstracering en het Mollie-versienummer.

---

## Een probleem melden

Verzamel de volgende informatie voordat je een bug meldt of ondersteuning aanvraagt:

- **Extensieversie**: zichtbaar in **Stores → Configuration → Mollie → General → Mollie Configuration** naast de Version-knop, of via `composer show mollie/magento2 | grep versions`
- **Magento-versie en -editie**: zichtbaar in de voettekst van Magento Admin, of via `php bin/magento --version`
- **PHP-versie**: `php -v`
- **Zelftestresultaten**: voer de zelftest uit en kopieer of maak een screenshot van de volledige uitvoer
- **Debug-pakket**: klik op **Download debug information** (zie hierboven) voor een geanonimiseerd archief van logboeken en configuratie
- **Stappen om te reproduceren**: de exacte reeks acties die het probleem veroorzaakt, inclusief welke betaalmethode werd gebruikt en of dit in test- of live-modus was
- **Order-ID of transactie-ID** (indien van toepassing): het Magento-orderincrementele ID en/of het Mollie-transactie-ID van het Mollie Dashboard

Meld problemen via [github.com/mollie/magento2/issues](https://github.com/mollie/magento2/issues). Voeg het debug-pakket en de zelftestuitvoer toe in plaats van onbewerkte loginhoud te plakken, want het pakket bevat de context die nodig is om de meeste problemen te diagnosticeren.

## Volgende stappen

- [Installatie](INSTALLATION.md) - Installatiestappen en systeemvereisten
- [API-sleutels](API_KEYS.md) - Je Mollie API-sleutels vinden en configureren
- [Configuratie](CONFIGURATION.md) - Alle algemene instellingen toegelicht
- [Best Practices](BEST_PRACTICES.md) - Aanbevolen configuratie voor productie
- [Creditcardbetalingen](CREDIT_CARD.md) - Configuratie van Mollie Components
