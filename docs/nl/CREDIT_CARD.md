# Creditcardbetalingen

Deze pagina beschrijft hoe je de creditcardbetaalmethode configureert, inclusief Mollie Components voor ingebedde kaartinvoer, capture-modus en opgeslagen kaarten voor terugkerende klanten.

## Vereisten

- Mollie Payments voor Magento 2 is geïnstalleerd en een API-sleutel is geconfigureerd - zie [API Keys](API_KEYS.md)
- Om Mollie Components te gebruiken, moet je Profile ID zijn opgeslagen in de algemene instellingen - zie [Configuration](CONFIGURATION.md)

## Creditcardbetalingen inschakelen

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Klap **Credit Cards** uit
3. Zet **Enabled** op **Yes**
4. Klik op **Save Config** en leeg de cache - betaalmethoden zijn niet zichtbaar voor klanten totdat de cache is geleegd

## Mollie Components

Mollie Components integreert het kaartinvoerformulier direct op je checkoutpagina, zodat klanten je winkel nooit verlaten om hun kaartgegevens in te voeren. Zonder Components worden klanten doorgestuurd naar een gehoste betaalpagina op Mollie's servers.

**Belangrijk:** Mollie Components vereist een Profile ID. Als het veld Profile ID onder **Stores → Configuration → Mollie → General** leeg is, schakelt de checkout automatisch over naar de gehoste redirectflow.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Cards** uit
2. Zet **Use Mollie Components** op **Yes**
3. Klik op **Save Config** en leeg de cache

Het kaartformulier wordt inline weergegeven bij de betaalstap van de checkout. Klanten voeren hun kaartnummer, vervaldatum en CVC in zonder de pagina te verlaten.

## Capture-modus

Creditcardbetalingen ondersteunen een keuze tussen automatische en handmatige capture. Bij automatische capture wordt de betaling direct afgerond wanneer de klant autoriseert. Bij handmatige capture wordt de betaling geautoriseerd maar worden de middelen niet verplaatst totdat je de capture activeert vanuit Magento Admin.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Cards** uit
2. Zet **Capture method** op **Autocapture** of **Manual capture**
3. Klik op **Save Config** en leeg de cache

### Handmatige capture

Wanneer **Manual capture** is geselecteerd, stel je in wanneer de capture wordt geactiveerd.

1. Zet **When to capture?** op een van de volgende opties:
   - **On invoice** - de capture wordt naar Mollie gestuurd wanneer je een factuur aanmaakt voor de order
   - **On shipment** - de capture wordt verstuurd wanneer je een zending aanmaakt; de extensie maakt automatisch de factuur aan vóór de capture
2. Stel optioneel een **Capture expiration window** in om te beperken hoe lang een autorisatie open kan blijven voordat deze automatisch wordt vrijgegeven

**Belangrijk:** Een autorisatie die verloopt voordat deze is gecaptured, wordt automatisch vrijgegeven door Mollie. Eenmaal vrijgegeven kan de order niet meer worden gecaptured en moet worden geannuleerd.

### Automatische capture met vertraging

Voor **Autocapture** kun je een vertraging instellen tussen autorisatie en afrekening om jezelf een venster te geven om orders te beoordelen of te annuleren.

1. Zet **Capture method** op **Autocapture**
2. Voer een waarde in bij **Capture delay** en selecteer **Hours** of **Days** als eenheid
3. Klik op **Save Config** en leeg de cache

## Opgeslagen kaarten

Wanneer opgeslagen kaarten zijn ingeschakeld, kunnen ingelogde klanten hun kaart opslaan na een geslaagde betaling en deze bij toekomstige checkouts gebruiken zonder hun gegevens opnieuw in te voeren. Dit maakt gebruik van de [Mollie Customers API](https://docs.mollie.com/docs/saving-a-card-for-returning-customers).

### Opgeslagen kaarten inschakelen

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Cards** uit
2. Zet **Enable saved cards** op **Yes**
3. Klik op **Save Config** en leeg de cache

Bij de checkout verschijnt een selectievakje met de toestemmingstekst wanneer de klant betaalt met een creditcard. De kaart wordt alleen opgeslagen als de klant het vakje aanvinkt.

### De toestemmingstekst aanpassen

De tekst naast het selectievakje voor kaartopslag is aanpasbaar. Het ondersteunt twee tijdelijke aanduidingen die tijdens gebruik worden vervangen:

| Tijdelijke aanduiding | Vervangen door |
|---|---|
| `{{tradingname}}` | De naam van de winkel |
| `{{supportcontact}}` | Het algemene contact-e-mailadres |

Om een link op te nemen, gebruik je de Markdown-linksyntaxis: `[linktekst](https://voorbeeld.com)`.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Cards** uit
2. Pas het veld **Consent text** aan
3. Klik op **Save Config** en leeg de cache

### Opgeslagen kaarten beheren

Klanten kunnen hun opgeslagen kaarten bekijken en verwijderen via **My Account → Saved Cards**. Het verwijderen van een kaart trekt het mandaat in bij Mollie, zodat het niet kan worden gebruikt voor toekomstige betalingen.

## Land- en ordertotaalbeperkingen

Beperk de creditcardoptie tot specifieke landen of orderbedragen.

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Cards** uit
2. Om per land te beperken, zet **Payment from Applicable Countries** op **Specific Countries** en selecteer de toegestane landen bij **Payment from Specific Countries**
3. Om per orderbedrag te beperken, voer waarden in bij **Minimum Order Total** en/of **Maximum Order Total**
4. Klik op **Save Config** en leeg de cache

## Betalingstoeslag

Een toeslag kan worden toegevoegd aan creditcardorders om verwerkingskosten te compenseren. Zie [Payment Fee](PAYMENT_FEE.md) voor configuratiedetails.

## Volgende stappen

- [Configuration](CONFIGURATION.md) - Algemene instellingen inclusief Profile ID
- [Saved Cards](SAVED_CARDS.md) - Volledige handleiding voor de functie opgeslagen kaarten
- [Order Management](ORDER_MANAGEMENT.md) - Capture-, facturerings- en terugbetalingsgedrag
- [Payment Fee](PAYMENT_FEE.md) - Een toeslag toevoegen aan betaalmethoden
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met kaartbetalingen
