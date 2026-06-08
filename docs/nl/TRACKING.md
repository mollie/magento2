# Verzendtracking en Analytics-cookies

Mollie Payments for Magento 2 biedt twee trackingfuncties: capture op basis van verzending, waarbij Mollie wordt genotificeerd wanneer je een bestelling verzendt en de betaalafwikkeling voor buy-now-pay-later-methoden wordt getriggerd, en het doorsturen van analytics-cookies, waarbij first-party tracking-cookies worden meegestuurd via de Mollie-doorverwijzing zodat je analytics-attributie intact blijft.

## Vereisten

- Mollie Payments for Magento 2 is geinstalleerd en een API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)
- Voor capture op basis van verzending: de betreffende betaalmethode moet geconfigureerd zijn met **Manual capture** en **On shipment** - zie [Order Management](ORDER_MANAGEMENT.md)

---

## Capture op basis van verzending

### Waarom dit belangrijk is voor buy-now-pay-later

Buy-now-pay-later-methoden zoals Klarna en Billie autoriseren de betaling wanneer de klant de bestelling plaatst, maar wikkelen deze niet direct af. Mollie vereist een verzendingsnotificatie voordat het bedrag aan jou wordt uitbetaald. Het aanmaken van een verzending in Magento Admin is de trigger voor die notificatie. Als je nooit een verzending aanmaakt in Magento, verloopt de autorisatie en kan de bestelling niet worden vastgelegd.

### Welke betaalmethoden gebruiken capture op basis van verzending

| Methode | Standaard capture-trigger | Aanpasbaar |
|---|---|---|
| Klarna | On shipment | Yes |
| Billie | On shipment | Yes |
| Credit Card | On invoice | Yes |
| Mobile Pay | On invoice | Yes |
| Vipps | On invoice | Yes |
| Riverty | On shipment (fixed) | No |

Voor Klarna en Billie is de standaard **On shipment**, wat aansluit bij de buy-now-pay-later-stroom. Riverty gebruikt altijd manual capture op verzending en toont de instelling niet in Admin.

### Instellen wanneer capture wordt getriggerd

Elke methode die manual capture ondersteunt, heeft zijn eigen instelling **When to capture?**.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Vouw de betaalmethode uit die je wilt configureren (bijvoorbeeld **Klarna**)
3. Stel **Capture method** in op **Manual capture**
4. Stel **When to capture?** in op **On shipment** of **On invoice**
5. Klik op **Save Config** en leeg de cache

**On shipment** - de extensie maakt automatisch een factuur aan voor de verzonden artikelen en stuurt het capture-verzoek naar Mollie wanneer je de verzending opslaat.

**On invoice** - de capture wordt naar Mollie gestuurd wanneer je handmatig een factuur aanmaakt vanuit de bestelling in Magento Admin.

### Een verzending aanmaken en capture triggeren

1. Ga naar **Sales → Orders** en open de bestelling
2. Klik op **Ship**
3. Vul de verzendgegevens in, inclusief eventuele trackinginformatie van je vervoerder
4. Klik op **Submit Shipment**

De extensie legt de verzonden artikelen vast bij Mollie in hetzelfde verzoek. De Magento-verzendregistratie slaat het Mollie-verzend-ID op als referentie.

**Belangrijk:** Als een gedeeltelijke verzending wordt aangemaakt, wordt alleen de waarde van de verzonden artikelen vastgelegd. Een tweede capture wordt gestuurd wanneer je een volgende verzending aanmaakt voor de resterende artikelen.

**Belangrijk:** Een autorisatie heeft een beperkte geldigheidstermijn. Als de termijn verstrijkt voordat er een verzending is aangemaakt in Magento, geeft Mollie de autorisatie automatisch vrij en kan de bestelling niet meer worden vastgelegd. Stel een **Capture expiration window** in bij de configuratie van de betaalmethode als je deze periode expliciet wilt afbakenen - zie [Order Management](ORDER_MANAGEMENT.md).

### Controleren of de capture is verstuurd

1. Ga naar **Sales → Orders** en open de bestelling
2. Open het tabblad **Invoices** - er moet een factuur aanwezig zijn voor de verzonden artikelen met de status **Paid**
3. Open het [Mollie Dashboard](https://my.mollie.com/dashboard) en zoek de bestelling op - de betaling moet als vastgelegd worden weergegeven

Als de factuur na het aanmaken van de verzending de status **Pending** heeft, is het capture-verzoek mislukt. Controleer `var/log/mollie.log` voor de foutdetails.

---

## Doorsturen van analytics-cookies

### Wat dit doet

Wanneer een klant een bestelling plaatst, leest de extensie geconfigureerde browsercookies (zoals de Google Analytics-cookie `_ga`) uit en stuurt hun waarden mee via de Mollie-betaaldoorverwijzing als queryparameters. Nadat de betaling is voltooid en de klant terugkeert naar je bevestigingspagina, worden dezelfde parameters toegevoegd aan de URL van de bevestigingspagina zodat client-side JavaScript ze kan uitlezen en de conversie correct kan toewijzen.

Cookiewaarden worden bij het indienen van de bestelling opgeslagen bij de winkelwagen en worden onbewerkt doorgestuurd - de extensie interpreteert of transformeert ze niet.

### Analytics-cookies configureren

De tabel **Tracking cookies** is vooraf gevuld met een rij voor de cookie `_ga`, met als alias `clientId`. Voeg rijen toe, verwijder ze of vervang ze om te passen bij de cookies die aanwezig zijn in je checkout.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Zoek de tabel **Tracking cookies**
3. Klik voor elke cookie die je wilt doorsturen op **Add cookie** en vul in:
   - **Cookie name** - de exacte naam van de browsercookie, bijvoorbeeld `_ga` of `_fbp`
   - **Alias / query param** - de naam van de queryparameter die wordt gebruikt in de doorverwijzings-URL en de bevestigingspagina-URL, bijvoorbeeld `clientId` of `fbp`. Gebruik alleen alfanumerieke tekens en underscores.
4. Verwijder rijen die je niet nodig hebt via de verwijderknop op elke rij
5. Klik op **Save Config** en leeg de cache

De alias moet uniek zijn binnen de tabel. Als twee rijen dezelfde alias hebben, wordt alleen de eerste gebruikt.

### Hoe cookies door de doorverwijzing stromen

Wanneer de klant het afrekenen indient:

1. De extensie leest de waarde van elke geconfigureerde cookie uit het browserverzoek.
2. De verzamelde waarden worden opgeslagen in de databasetabel `mollie_payment_tracking`, gekoppeld aan het winkelwagen-ID.
3. Elke alias en de bijbehorende ruwe cookiewaarde worden als queryparameters toegevoegd aan de Mollie-doorverwijzings-URL, bijvoorbeeld: `https://checkout.mollie.com/pay/...?clientId=GA1.2.123456789.1234567890`
4. Nadat de klant de betaling heeft voltooid en terugkeert naar je winkel, worden de parameters die aanwezig waren op de retour-URL toegevoegd aan de URL van de bevestigingspagina zodat analytics-scripts op die pagina ze kunnen uitlezen.

**Tip:** De standaardconfiguratie stuurt het Google Analytics-client-ID door onder de alias `clientId`. Als je Google Analytics 4 gebruikt, controleer dan of de cookienaam in je winkel overeenkomt met `_ga` voordat je vertrouwt op de standaardrij.

### Controleren of het doorsturen van cookies werkt

1. Schakel **Debug requests** in onder **Stores → Configuration → Mollie → General → Debug & Logging**
2. Plaats een testbestelling
3. Open `var/log/mollie.log` en zoek naar het transactieverzoek - het veld `redirectUrl` moet de alias en de bijbehorende waarde bevatten als queryparameters

Als alternatief kun je tijdens een testcheckout de browser-ontwikkelaarstools openen en de URL inspecteren waarnaar je wordt doorgestuurd wanneer je op **Pay** klikt. Als de geconfigureerde cookies aanwezig waren in de browser bij het afrekenen, verschijnen hun waarden als queryparameters in die URL.

---

## Volgende stappen

- [Order Management](ORDER_MANAGEMENT.md) - Capture-modi, vervaltermijnen en facturering
- [Klarna](KLARNA.md) - Klarna-specifiek capture-gedrag en facturering
- [Configuration](CONFIGURATION.md) - Alle algemene instellingen
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met capture en orderafwikkeling
