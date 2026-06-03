# Configuratie

Dit artikel behandelt alle algemene instellingen onder het tabblad **Mollie** in Magento Admin voor Mollie Payments voor Magento 2. Instellingen die specifiek zijn voor betaalmethoden staan beschreven in [Betaalmethoden](PAYMENT_METHODS.md).

## Vereisten

- De extensie is geïnstalleerd. Zie [Installatie](INSTALLATION.md).
- Je hebt een Mollie API-sleutel beschikbaar. Zie [API-sleutels](API_KEYS.md).

## Algemeen

### De extensie inschakelen

De globale aan/uit-schakelaar voor alle Mollie-betaalmethoden bij het afrekenen.

1. Ga naar **Stores → Configuration → Mollie → General**
2. Zet **Enabled** op **Yes**
3. Klik op **Save Config** en leeg de cache

Door dit op **No** te zetten verdwijnen alle Mollie-betaalmethoden bij het afrekenen zodra de cache is geleegd.

### API-sleutels en modus

Zie [API-sleutels](API_KEYS.md) voor de volledige uitleg. Samengevat:

1. Ga naar **Stores → Configuration → Mollie → General**
2. Zet **Modus** op **Test** of **Live**
3. Voer de bijbehorende API-sleutel in bij **Test API Key** of **Live API Key**
4. Klik op **Save Config**

Het veld **Profile ID** wordt automatisch ingevuld zodra je een geldige API-sleutel opslaat. Het kan niet direct worden bewerkt.

## Debug en logging

### Debugverzoeken

Wanneer ingeschakeld worden alle verzoeken aan en antwoorden van de Mollie API weggeschreven naar `var/log/mollie.log`.

![Sectie Debug & Logging met de velden Debug requests en Anonymize debug requests](../images/config-general-debug.png)

Deze instelling is globaal (niet store view-gebonden).

1. Ga naar **Stores → Configuration → Mollie → General → Debug & Logging**
2. Zet **Debug requests** op **Yes** of **No**
3. Klik op **Save Config**

Configuratiepad: `payment/mollie_general/debug`. Standaard: **Yes**. Schakel dit uit op productieomgevingen nadat je hebt bevestigd dat de integratie correct werkt, om onbeperkte loggroei te voorkomen.

### Debugverzoeken anonimiseren

Als debug-logging is ingeschakeld, verwijdert deze optie persoonsgegevens (klantnamen, e-mailadressen en kaartgegevens) uit de logregels voordat ze worden weggeschreven.

1. Ga naar **Stores → Configuration → Mollie → General → Debug & Logging**
2. Zet **Anonymize debug requests** op **Yes** of **No**
3. Klik op **Save Config**

Configuratiepad: `payment/mollie_general/anonymize_debug_requests`. Standaard: **Yes**. Dit veld is alleen zichtbaar wanneer **Debug requests** is ingeschakeld.

## Orderbeheer

### Orderstatussen

Twee statussen bepalen de levenscyclus van elke Mollie-order.

Zie [Orderbeheer](ORDER_MANAGEMENT.md) voor de volledige uitleg.

| Veld | Configuratiepad | Standaard |
|---|---|---|
| **Status Pending** | `payment/mollie_general/order_status_pending` | `pending_payment` |
| **Status Processing** | `payment/mollie_general/order_status_processing` | `processing` |

1. Ga naar **Stores → Configuration → Mollie → Order Management → Statuses**
2. Zet **Status Pending** op de status die wordt toegewezen wanneer de klant wordt doorgestuurd naar de betaalpagina
3. Zet **Status Processing** op de status die wordt toegewezen na een bevestigde betaling
4. Klik op **Save Config** en leeg de cache

### Factuursamenstelling en e-mail

1. Ga naar **Stores → Configuration → Mollie → Order Management → Advanced**
2. Zet **Create invoice on successful payment** op **Yes** (standaard) of **No**
3. Ga naar **Stores → Configuration → Mollie → Order Management → Invoicing & Surcharges**
4. Zet **Send Invoice Email** op **Yes** (standaard) om de Magento-factuur-e-mail naar de klant te sturen
5. Zet **Send Invoice Email For Klarna Orders** op **Yes** (standaard) of **No** (Klarna verstuurt zijn eigen factuurcommunicatie)
6. Klik op **Save Config** en leeg de cache

Configuratiepaden: `payment/mollie_general/create_invoice`, `payment/mollie_general/invoice_notify`, `payment/mollie_general/invoice_notify_klarna`.

### Berekeningsbasis toeslag

Deze instellingen bepalen welke bedragen worden meegenomen bij het berekenen van een procentuele betalingstoeslag. Ze zijn store view-gebonden.

1. Ga naar **Stores → Configuration → Mollie → Order Management → Invoicing & Surcharges**
2. Zet **Include shipping in Surcharge calculation** op **Yes** om de verzendkosten toe te voegen aan de toeslagbasis, of **No** om alleen het subtotaal te gebruiken
3. Zet **Include discount in Surcharge calculation** op **Yes** om het kortingsbedrag toe te voegen aan de toeslagbasis, of **No** om dit uit te sluiten
4. Klik op **Save Config** en leeg de cache

Configuratiepaden: `payment/mollie_general/include_shipping_in_surcharge`, `payment/mollie_general/include_discount_in_surcharge`. Zie [Betalingstoeslag](PAYMENT_FEE.md) voor de volledige toeslagconfiguratie.

### Valuta

1. Ga naar **Stores → Configuration → Mollie → Order Management → Triggers & Languages**
2. Zet **Use Base Currency** op **Yes** (standaard) om altijd de basisvaluta van de winkel naar Mollie te sturen, of **No** om de valuta te sturen die de klant heeft geselecteerd in de store view
3. Klik op **Save Config** en leeg de cache

Configuratiepad: `payment/mollie_general/currency`. Standaard: **Yes**.

Bij **No** moet je controleren of de door de klant geselecteerde valuta is ingeschakeld in je Mollie-profiel. Het sturen van een niet-ondersteunde valuta leidt tot een betaalfout.

### Taal van de betaalpagina

Bepaalt de taalinstelling die Mollie gebruikt op de gehoste betaalpagina.

1. Ga naar **Stores → Configuration → Mollie → Order Management → Triggers & Languages**
2. Zet **Language Payment Page** op een van de volgende opties:
   - **Autodetect** (standaard): Mollie detecteert de taalinstelling op basis van de browser van de klant. Voor betaalmethoden die de Orders API gebruiken, wordt in plaats daarvan de store view-taalinstelling gebruikt.
   - **Store Locale**: de taalinstelling die is geconfigureerd op de huidige store view, met Engels als terugvaloptie als deze niet kan worden bepaald.
   - Een specifieke taalinstelling uit de lijst (bijvoorbeeld `nl_NL`, `de_DE`, `fr_FR`)
3. Klik op **Save Config** en leeg de cache

Configuratiepad: `payment/mollie_general/locale`.

## Ontwikkelaarsinstellingen

### Webhooks

Standaard stuurt de extensie de eigen winkel-URL als het Mollie-webhookendpoint.

Wijzig dit alleen voor headless storefronts, PWA-integraties of lokale ontwikkelomgevingen waar de Mollie-servers de winkel niet rechtstreeks kunnen bereiken.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Zet **Use webhooks** op een van de volgende opties:
   - **Enabled** (standaard): de extensie bouwt de webhook-URL op basis van de basis-URL van de winkel (`/mollie/checkout/webhook/`) en stuurt deze met elke order naar Mollie
   - **Custom URL**: de extensie gebruikt de URL uit het veld **Custom webhook url** in plaats van het ingebouwde endpoint; de order-ID's worden automatisch als queryparameters toegevoegd
   - **Disabled**: er wordt geen webhook-URL naar Mollie gestuurd; Mollie zal de winkel niet informeren over betalingsupdates (alleen geschikt voor lokale ontwikkeling)
3. Als **Custom URL** is geselecteerd, voer de doel-URL in bij **Custom webhook url**
4. Klik op **Save Config** en leeg de cache

Configuratiepaden: `payment/mollie_general/use_webhooks` (standaard: `enabled`), `payment/mollie_general/custom_webhook_url`.

**Belangrijk:** Als webhooks zijn uitgeschakeld in productie, worden orders nooit automatisch bijgewerkt. Alleen de cron job voor openstaande orders (indien ingeschakeld) zal betalingsbevestigingen verwerken.

### Transacties verwerken via de wachtrij

Wanneer ingeschakeld worden webhookcallbacks asynchroon verwerkt via de `mollie.transaction.processor` Message Queue (MQ) consumer in plaats van synchroon tijdens het webhookverzoek. Dit voorkomt webhooktimeouts op winkels waarbij werk na de betaling (factuursamenstelling, bevestigingsemails en ERP-aanroepen) lang duurt.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Zet **Process transactions in the queue** op **Yes** (standaard) of **No**
3. Klik op **Save Config**

Configuratiepad: `payment/mollie_general/process_transactions_in_the_queue`. Standaard: **Yes**.

Als de wachtrij-consumer niet actief is, worden webhooks wel ontvangen maar orders niet bijgewerkt. Naast dit veld verschijnt een waarschuwing als de wachtrij niet correct is geconfigureerd. Voer de zelftest uit vanuit de General-sectie om wachtrijproblemen te diagnosticeren. Zie [Best practices](BEST_PRACTICES.md) voor begeleiding bij het instellen van consumers.

### Tracking-cookies

De tabel met tracking-cookies koppelt browsercookies aan queryparameter-aliassen. Voor elke rij leest de extensie de genoemde cookie op het moment dat de klant de checkout indient, en voegt de onbewerkte waarde als queryparameter toe aan de Mollie-redirect-URL onder de opgegeven alias. Dezelfde waarde wordt opgeslagen op de orderbevestigingspagina zodat analyticscripts deze kunnen uitlezen voor attributie.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Klik in de tabel **Tracking cookies** op **Add** om een rij toe te voegen
3. Voer de **Cookie name** in (de naam van de browsercookie die moet worden vastgelegd, bijvoorbeeld `_ga`)
4. Voer de **Alias** in (de te gebruiken queryparameternaam, bijvoorbeeld `clientId`)
5. Herhaal voor elke cookie die moet worden vastgelegd
6. Klik op **Save Config** en leeg de cache

Configuratiepad: `payment/mollie_general/tracking_cookies`. De standaardconfiguratie legt `_ga` vast onder de alias `clientId`.

Rijen met een lege cookienaam of een dubbele alias worden stilzwijgend overgeslagen. Zie [Tracking](TRACKING.md) voor de volledige trackingintegratiegids.

#### Aangepaste terugkeer-URL

Na het voltooien van een betaling stuurt Mollie de klant terug naar de winkel. Standaard is dit de door Magento gehoste verwerkings-URL. Een PWA kan dit overschrijven met een aangepaste URL.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → PWA Storefront Integration**
2. Zet **Use custom return url?** op **Yes**
3. Voer de doel-URL in bij **Custom return url**
4. Klik op **Save Config** en leeg de cache

Configuratiepaden: `payment/mollie_general/use_custom_redirect_url` (standaard: **No**), `payment/mollie_general/custom_redirect_url`.

De aangepaste terugkeer-URL ondersteunt de volgende plaatshouders:

| Plaatshouder | Waarde |
|---|---|
| `{{order_id}}` | Entity-ID van de order |
| `{{increment_id}}` | Increment-ID (ordernummer) |
| `{{payment_token}}` | Gegenereerde betaaltoken |
| `{{order_hash}}` | Entity-ID versleuteld en base64-gecodeerd |
| `{{base_url}}` | Basis-URL van de winkel |
| `{{unsecure_base_url}}` | Basis-URL van de winkel (onbeveiligd) |
| `{{secure_base_url}}` | Basis-URL van de winkel (beveiligd) |

De URL moet het protocol bevatten (`https://`). Het veld **Custom return url** is alleen zichtbaar wanneer **Use custom return url?** op **Yes** staat.

#### Aangepaste betaallink-URL

De betaallink-URL wordt gebruikt door de Second Chance Email-functie en de betaalmethode Payment Link. Overschrijf deze wanneer de klantgerichte link via een PWA of aangepast domein moet worden verwerkt.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → PWA Storefront Integration**
2. Zet **Use custom payment link url?** op **Yes**
3. Voer de doel-URL in bij **Custom payment link url**
4. Klik op **Save Config** en leeg de cache

Configuratiepaden: `payment/mollie_general/use_custom_paymentlink_url` (standaard: **No**), `payment/mollie_general/custom_paymentlink_url`.

De plaatshouder `{{order}}` is vereist in de URL - deze wordt vervangen door de versleutelde entity-ID van de order. Het veld is alleen zichtbaar wanneer **Use custom payment link url?** op **Yes** staat.

## Volgende stappen

- [API-sleutels](API_KEYS.md) — API-sleutels invoeren en rouleren
- [Betaalmethoden](PAYMENT_METHODS.md) — Individuele betaalmethoden inschakelen en configureren
- [Orderbeheer](ORDER_MANAGEMENT.md) — Statussen, facturering, vastlegging en terugbetalingen
- [Second Chance Email](SECOND_CHANCE_EMAIL.md) — Geautomatiseerde betalingsherinneringen
- [Betalingstoeslag](PAYMENT_FEE.md) — Toeslagconfiguratie
- [Tracking](TRACKING.md) — Cookie- en analyticstracking
- [Best practices](BEST_PRACTICES.md) — Aanbevolen productie-instellingen
- [Problemen oplossen](TROUBLESHOOTING.md) — Veelvoorkomende configuratieproblemen
