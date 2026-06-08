# Best Practices

Dit artikel behandelt aanbevolen configuratie- en beheerpraktijken voor het draaien van Mollie Payments in productie.

## Voer de zelftest uit na de installatie

Voer na het installeren en configureren van de extensie de ingebouwde zelftest uit om veelvoorkomende configuratieproblemen op te sporen voordat je live gaat.

1. Ga naar **Stores → Configuration → Mollie → General**
2. Vouw **Mollie Configuration** uit en klik op **Run Self-test**
3. Los eventuele fouten of waarschuwingen op voordat je live betalingen accepteert

De zelftest controleert: PHP-versie, vereiste extensies, bereikbaarheid van de webhook, wachtrijconfiguratie, Apple Pay-domeinvalidatie en meer.

## Webhooks

### Houd de webhook-URL publiek toegankelijk

De Mollie webhook-URL (`/mollie/checkout/webhook/`) moet POST-verzoeken van Mollie's servers accepteren zonder doorverwijzingen of authenticatievereisten. Een webhook die doorverwijst (HTTP 3xx) of een fout retourneert (HTTP 4xx/5xx) zorgt ervoor dat orderstatusupdates mislukken.

Controleer het volgende op productieservers:

- Firewall- of WAF-regels die POST-verzoeken van externe IP-adressen blokkeren of beperken
- Onderhoudsmodus-pagina's die alle verzoeken onderscheppen
- Botbeveiliging die niet-browsertrafiek uitdaagt

### Cloudflare

Als de webshop achter Cloudflare staat, stel dan een regel in om botbeveiliging te omzeilen voor de Mollie webhook-URL en de return-URL. Cloudflare's standaard botdetectie daagt geautomatiseerde POST-verzoeken uit, waardoor webhooks niet verwerkt worden. Zie de [Cloudflare-configuratiehandleiding](https://github.com/mollie/magento2/wiki/Cloudflare-Configuration-for-Mollie-Webhooks) voor de specifieke regels die je moet aanmaken.

### GeoIP-modules

GeoIP-modules die bezoekers op basis van hun locatie doorverwijzen naar een winkel, kunnen webhook-verzoeken herschrijven of blokkeren. Dit komt doordat Mollie's servers afkomstig zijn van IP-adressen die kunnen worden omgezet naar een ander land of een andere winkel. Sluit de webhook-URL en de return-URL uit van elke GeoIP-doorverwijzing of winkelwissellogica.

## Transactieverwerking

### Houd wachtrijverwerking ingeschakeld

Op wachtrij gebaseerde transactieverwerking is standaard ingeschakeld. Wanneer een webhook binnenkomt, wordt de orderupdate in de wachtrij `mollie.transaction.processor` geplaatst en asynchroon verwerkt. Dit voorkomt webhook-time-outs op winkels waar bevestigingsmails, facturen en ander werk na betaling lang duren.

Controleer via de zelftest na de installatie of de consumer actief is. Als de wachtrij is geconfigureerd maar de consumer niet actief is, worden webhooks geaccepteerd maar orders niet bijgewerkt.

Als je draait op een platform met een toegestane lijst van consumers, voeg dan `mollie.transaction.processor` toe aan de toegestane consumers.

### Schakel de cron job voor openstaande orders in

Schakel de cron job voor openstaande orders in om orders die vastzitten in de wachtstatus automatisch te herstellen als gevolg van gemiste of mislukte webhooks.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Zet **Enable Pending Orders Cron Job** op **Yes**

De standaard batchgrootte van 25 is voor de meeste winkels geschikt. Verklein dit voor winkels met beperkte cron-resources.

## Orderbeheer

### Gebruik een unieke status voor openstaande bankoverschrijvingen

Bankoverschrijvingsbetalingen kunnen meerdere dagen openstaan. Het gebruik van de standaard `pending_payment`-status voor deze orders maakt het moeilijk om ze te onderscheiden van andere onverwerkte orders in het beheeroverzicht.

Maak een speciale orderstatus aan (bijvoorbeeld `pending_banktransfer`) en wijs deze toe onder **Stores → Configuration → Mollie → Order Management**.

### Schakel tweede kans e-mails in voor afgebroken checkouts

Tweede kans e-mails sturen een betaallink naar klanten die een betaling zijn begonnen maar niet hebben afgerond. Schakel automatisch verzenden in om deze orders te herstellen zonder handmatige tussenkomst.

1. Ga naar **Stores → Configuration → Mollie → Second Chance Email**
2. Zet **Enable Second Chance Email** op **Yes**
3. Zet **Automatically Send Second Chance Emails** op **Yes**
4. Stel de vertraging en het e-mailsjabloon in passend bij de communicatiestijl van de winkel

Alleen orders die openstaand blijven en waarvoor geen voltooide transactie bestaat op hetzelfde e-mailadres, ontvangen de e-mail.

## Betaalmethoden

### Schakel de Methods API in

De Methods API filtert de betaalmethoden die bij de checkout worden getoond op basis van het land van de klant en het totaal van de winkelwagen. Dit voorkomt dat klanten methoden selecteren die niet beschikbaar zijn voor hun bestelling.

1. Ga naar **Stores → Configuration → Mollie → Developer Settings → Advanced**
2. Zet **Enable the Methods API** op **Yes**

Dit vereist één extra API-aanroep per checkout-sessie. Op winkels waar API-latentie een punt van zorg is, schakel je het uit en configureer je in aanmerking komende methoden handmatig.

## Beveiliging

### Schakel versleuteling in voor betalingsgegevens

Kaartmetadata (kaarttype, laatste vier cijfers, enzovoort) wordt bij elke order opgeslagen. Schakel versleuteling in om deze gegevens versleuteld op te slaan.

1. Ga naar **Stores → Configuration → Mollie → Order Management**
2. Zet **Encrypt Payment Details** op **Yes**

### Gebruik aparte API-sleutels per omgeving

Gebruik nooit een live API-sleutel op een staging- of ontwikkelomgeving. Maak een apart Mollie-profiel aan of gebruik de test API-sleutel op alle niet-productiewinkels. Dit voorkomt dat testorders in live rapportages verschijnen en vermijdt onbedoelde betalingen.

Bij het draaien van meerdere websites in één Magento-installatie, stel je API-sleutels in op website-niveau zodat elke site het juiste profiel en de juiste modus gebruikt.

## Volgende stappen

- [Configuratie](CONFIGURATION.md) - Alle algemene instellingen toegelicht
- [Probleemoplossing](TROUBLESHOOTING.md) - Veelvoorkomende problemen en oplossingen
