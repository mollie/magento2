# Opgeslagen kaarten

Met opgeslagen kaarten kunnen ingelogde klanten een creditcard opslaan na een geslaagde betaling en deze bij toekomstige checkouts gebruiken zonder hun gegevens opnieuw in te voeren. Mollie Payments voor Magento 2 gebruikt de [Mollie Customers API](https://docs.mollie.com/docs/saving-a-card-for-returning-customers) om de kaart als mandaat op te slaan bij een klantenrecord, en geeft dat mandaat-ID door bij volgende orders.

## Vereisten

- De creditcardbetaalmethode is ingeschakeld - zie [Credit Card Payments](CREDIT_CARD.md)
- Mollie Components is ingeschakeld (`Use Mollie Components` ingesteld op `Yes`) - opgeslagen kaarten is alleen beschikbaar met het ingebedde kaartformulier, niet met de gehoste redirectflow
- Een Profile ID is opgeslagen onder **Stores → Configuration → Mollie → General** - Components vereist dit

## Opgeslagen kaarten inschakelen

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Card** uit
2. Zet **Enable saved cards** op **Yes**
3. Klik op **Save Config** en leeg de cache - de optie voor kaartopslag verschijnt niet bij de checkout totdat de cache is geleegd

Opgeslagen kaarten worden alleen aangeboden aan ingelogde klanten. Gastklanten zien het selectievakje voor kaartopslag nooit.

## Klanttoestemming bij de checkout

Wanneer een ingelogde klant Creditcard selecteert bij de checkout, verschijnen het selectievakje voor kaartopslag en de toestemmingstekst onder het kaartinvoerformulier.

De flow werkt als volgt:

1. De klant voert zijn kaartgegevens in via Mollie Components
2. Onder het kaartformulier verschijnt een selectievakje met het label **Save your card for faster checkout** naast de geconfigureerde toestemmingstekst
3. Als de klant het vakje aanvinkt, stuurt de extensie `storeCredentials: true` naar Mollie wanneer de order wordt geplaatst
4. Mollie slaat de kaart op als een geldig mandaat onder het Mollie-profiel van de klant en retourneert het mandaat-ID
5. Bij het volgende bezoek verschijnen de opgeslagen kaarten van de klant als keuzerondjes boven het kaartinvoerformulier - de klant kan een opgeslagen kaart selecteren of kiezen voor **Use a new card**

Wanneer een opgeslagen kaart is geselecteerd, wordt het kaartformulier verborgen en wordt de order geplaatst met het opgeslagen mandaat-ID. Er hoeven geen nieuwe kaartgegevens te worden ingevoerd.

### De toestemmingstekst aanpassen

De toestemmingstekst naast het selectievakje is aanpasbaar per winkelweergave. Het ondersteunt twee tijdelijke aanduidingen die tijdens gebruik worden vervangen:

| Tijdelijke aanduiding | Vervangen door |
|---|---|
| `{{tradingname}}` | De winkelnaam (uit **Stores → Configuration → General → Store Information → Store Name**) |
| `{{supportcontact}}` | Het algemene contact-e-mailadres (uit **Stores → Configuration → General → Store Email Addresses → General Contact**) |

Om een hyperlink in de toestemmingstekst op te nemen, gebruik je de Markdown-linksyntaxis: `[linktekst](https://voorbeeld.com)`.

De standaardtekst luidt:

> By saving your card, you authorise `{{tradingname}}` to charge your card for future purchases in accordance with our [privacy policy](https://example.com/privacy-policy). To revoke this authorisation, contact us at `{{supportcontact}}`.

Om deze te wijzigen:

1. Ga naar **Stores → Configuration → Mollie → Payment Methods** en klap **Credit Card** uit
2. Pas het veld **Consent text** aan
3. Klik op **Save Config** en leeg de cache

Pas de URL van het privacybeleid in de standaardtekst aan zodat deze verwijst naar je eigen privacypagina voordat je live gaat.

## Opgeslagen kaarten beheren (Mijn account)

Wanneer opgeslagen kaarten zijn ingeschakeld, verschijnt een link **Saved cards** in de navigatie van het klantenaccount onder **My Account**. Klanten bereiken de pagina via `/mollie/savedcards/index`.

De pagina toont alle geldige creditcardmandaten. Elke rij toont:

- Het logo van het kaartnetwerk (Visa, Mastercard, American Express, Maestro, Carte Bancaire of V PAY)
- De naam van het kaartnetwerk en de laatste vier cijfers van het kaartnummer
- De vervaldatum van de kaart

Om een kaart te verwijderen, klikt de klant op **Remove saved card** naast het item en bevestigt de vraag. De extensie trekt het mandaat direct in via de Mollie API. Eenmaal ingetrokken verschijnt de kaart niet meer bij de checkout en kan deze niet worden belast.

De link **Saved cards** verschijnt alleen in de accountnavigatie wanneer opgeslagen kaarten zijn ingeschakeld. Als je de functie uitschakelt nadat klanten al kaarten hebben opgeslagen, worden de link en pagina ontoegankelijk, maar de onderliggende mandaten in Mollie blijven bestaan totdat ze worden ingetrokken.

## Zichtbaarheid in Admin

De extensie voegt geen aparte tabel voor opgeslagen kaarten toe aan Magento Admin. Je kunt het Mollie-klant-ID en alle mandaten voor elke klant echter direct bekijken in het [Mollie Dashboard](https://www.mollie.com/dashboard) door de klant op te zoeken onder **Customers**.

Het toestemmingsauditlog wordt opgeslagen in de databasetabel `mollie_saved_card_consent`. Elke rij registreert het order-ID, het winkel-ID en het tijdstip waarop de klant het selectievakje voor toestemming heeft aangevinkt. Dit log wordt bewaard zolang de bijbehorende verkooporder bestaat - als een order wordt verwijderd, wordt het toestemmingsrecord ook verwijderd.

## Wat er gebeurt als een kaart wordt verwijderd

Wanneer een klant een opgeslagen kaart verwijdert via **My Account → Saved cards**, roept de extensie `mandates->revokeForId()` aan op de Mollie API. De intrekking wordt geautoriseerd door te verifiëren dat het mandaat behoort tot het Mollie-profiel van de momenteel ingelogde klant - een klant kan het mandaat van een andere klant niet intrekken.

Na intrekking:

- De kaart wordt direct verwijderd uit de lijst met opgeslagen kaarten van de klant
- De kaart verschijnt niet meer als optie bij de checkout
- Elke toekomstige betaling die het oude mandaat-ID gebruikt, wordt door Mollie geweigerd
- Bestaande orders die al zijn belast met het mandaat worden niet beïnvloed

## Beveiliging en compliance

- Kaartgegevens worden nooit opgeslagen in Magento. Alleen het Mollie-mandaat-ID wordt opgeslagen als aanvullende betalingsinformatie bij de order.
- Het Mollie-profielid van de klant (`cst_...`) wordt opgeslagen in de tabel `mollie_payment_customer`, gekoppeld aan het Magento-klantenrecord. Dit is een niet-gevoelige referentie-identificator.
- Mandaatintrekking vereist dat de klant is ingelogd. De extensie verifieert het eigenaarschap voordat de Mollie API wordt aangeroepen, waardoor ongeautoriseerde verwijderingen worden voorkomen.
- Elke verwijderactie via **My Account** is beveiligd met een CSRF-formuliersleutel.
- Expliciete schriftelijke toestemming wordt verzameld bij de checkout en voorzien van een tijdstempel voordat de kaart wordt opgeslagen. Controleer je lokale gegevensbeschermingsvereisten (zoals de AVG) en werk de toestemmingstekst bij met een link naar je privacybeleid voordat je de functie inschakelt.
- De Mollie Customers API valt onder de eigen beveiligingscontroles van Mollie en PCI DSS-compliance. Kaartgegevens worden nooit via je Magento-server verzonden.

## Volgende stappen

- [Credit Card Payments](CREDIT_CARD.md) - Components inschakelen, capture-modus en andere kaartinstellingen
- [Configuration](CONFIGURATION.md) - Algemene instellingen inclusief Profile ID
- [API Keys](API_KEYS.md) - Je Mollie-account verbinden
- [Troubleshooting](TROUBLESHOOTING.md) - Veelvoorkomende problemen met kaartbetalingen
