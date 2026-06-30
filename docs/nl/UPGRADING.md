# Upgraden naar versie 3

Dit artikel is bedoeld voor ontwikkelaars en systeembeheerders die Mollie Payments voor Magento 2 upgraden van een 2.x-release naar 3.x. Gebruik deze pagina samen met de update-stappen in [Installatie](INSTALLATION.md).

## Vereisten

Controleer voordat je het pakket bijwerkt het volgende:

- je upgrade van een 2.x-release naar 3.x
- je maatwerk hebt dat direct tegen interne classes van de Mollie-extensie werkt
- je oudere Klarna-methoden of de losse analytics-addon gebruikt
- je de upgrade eerst op een stagingomgeving kunt testen

## Wijzigingen in versie 3

Versie 3 wijzigt de platformvereisten, de interne betaalflow en meerdere operationele standaarden.

- PHP **8.1** of hoger is vereist
- Magento Open Source / Adobe Commerce **2.4.5** of hoger is vereist
- `mollie/mollie-api-php` is bijgewerkt naar v3
- ondersteuning voor de Orders API is verwijderd; betalingen gebruiken nu alleen nog de Payments API
- queue-gebaseerde transactieverwerking staat standaard aan
- instellingen voor handmatige capture worden per betaalmethode geconfigureerd
- oude Klarna-methoden zijn verwijderd ten gunste van de enkele **Klarna**-methode
- de losse analytics-addon is samengevoegd met de hoofdmodule

## Upgrade-stappen

Gebruik deze stappen om de pakket-update uit te voeren en het resultaat te controleren.

1. Lees de breaking changes en gedragswijzigingen op deze pagina
2. Werk het pakket bij:

```bash
composer update mollie/magento2
```

3. Voer de Magento-upgrade-stappen uit:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

4. Gebruik [Installatie](INSTALLATION.md#een-bestaande-installatie-bijwerken) als je de volledige updatecontext nodig hebt
5. Voer in Magento Admin de ingebouwde zelftest uit onder **Stores → Configuration → Mollie → General → Mollie Configuration**
6. Plaats minimaal één end-to-end testbetaling in de modus die je daadwerkelijk gebruikt

## Breaking Changes

Controleer deze wijzigingen voordat je productie bijwerkt of maatwerk samenvoegt.

### PHP- en Dependency-vereisten

Ondersteuning voor PHP 7.3 en 7.4 is vervallen. Versie 3 vereist PHP 8.1+ en `mollie/mollie-api-php` v3.

### Verwijdering van de Orders API

Ondersteuning voor de Orders API is uit de extensie verwijderd. Het aanmaken van transacties verloopt nu uitsluitend via de Payments API.

Als je maatwerk hebt dat afhankelijk is van verwijderde Orders API-classes of wrappers, moet dat vóór of tijdens de upgrade worden aangepast. De belangrijkste verwijderde internals zijn:

- `Model/Client/Orders/*`
- `Service/Mollie/Wrapper/OrdersEndpointWrapper.php`

Maatwerk dat afhankelijk was van request- of response-structuren van de Orders API moet worden aangepast naar de Payments API-flow onder `Model/Client/Payments`.

### Oude Klarna-methoden Verwijderd

De oude Klarna-methoden zijn verwijderd ten gunste van de uniforme **Klarna**-betaalmethode:

- `klarnapaylater`
- `klarnapaynow`
- `klarnasliceit`

Als je historische orders met deze methoden nog moet terugbetalen, doe dat via het Mollie Dashboard.

### Capture-instellingen Per Methode

Handmatige capture is niet langer één globale instelling. Controleer na de upgrade de capture-configuratie per relevante betaalmethode.

De belangrijkste getroffen methoden zijn:

- Billie
- Credit Card
- Klarna
- MobilePay
- Vipps

### Queue-verwerking Standaard Ingeschakeld

Webhookverwerking in de queue staat standaard aan in versie 3. Als de consumer niet draait, worden orders wel aangemaakt maar mogelijk niet bijgewerkt na betaling.

Als je platform met een consumer-allowlist werkt, voeg dan toe:

```text
mollie.transaction.processor
```

Controleer daarna de queue-status met de zelftest en de uitleg in [Best Practices](BEST_PRACTICES.md) en [Probleemoplossing](TROUBLESHOOTING.md).

### Analytics-addon Samengevoegd

De losse `mollie/magento2-analytics`-module is samengevoegd met de hoofdmodule.

Tijdens `setup:upgrade` wordt data uit `mollie_analytics_analytics` gemigreerd naar `mollie_payment_tracking`, waarna de oude tabel wordt verwijderd. De oude `Mollie\Analytics\...`-namespace is niet langer beschikbaar.

Als je eerder afhankelijk was van server-side stripping van de `GA1.2.`-prefix uit de Google Analytics client ID, verwerk dat dan op de success-pagina:

```javascript
const params = new URLSearchParams(window.location.search);
const raw = params.get('clientId');
const clientId = raw?.split('.').slice(2).join('.') ?? null;
```

## Wijzigingen In Gedrag

Deze wijzigingen beïnvloeden het gedrag van de extensie na de upgrade, ook wanneer de pakket-update zelf zonder fouten is verlopen.

### Orders Geplaatst Vóór De Upgrade

Orders die op 2.x zijn geplaatst, bevatten een oud Orders API-transactie-id (`ord_...`). Versie 3 herleidt deze automatisch naar de onderliggende betaling, zodat een 2.x-order die nog openstaat correct wordt bijgewerkt zodra de klant betaalt. Voor statusverwerking is geen actie nodig.

Handmatige capture, het vrijgeven van autorisaties en refunds worden voor deze oude orders niet afgehandeld. Handel elke 2.x-order die nog geautoriseerd is en wacht op capture of annulering af via het Mollie Dashboard.

**Belangrijk:** Het omzetten van een betaalmethode naar automatische capture voert geen capture uit voor orders die al op 2.x waren geautoriseerd. De autorisatie blijft openstaan totdat je via het Mollie Dashboard een capture uitvoert of de order annuleert.

### Externe Refunds Synchroniseren Nu Terug Naar Magento

Refunds die direct in het Mollie Dashboard worden aangemaakt, worden nu automatisch gedetecteerd en omgezet naar Magento-creditmemo's.

### Factuurcreatie Kan Worden Uitgeschakeld

Als facturatie door een ERP of ander extern systeem wordt afgehandeld, kun je automatische factuurcreatie uitschakelen onder **Stores → Configuration → Mollie → Order Management → Advanced**.

### Order Annuleren Bij Terugkeer Naar Checkout

Versie 3 voegt een optie toe om een openstaande order automatisch te annuleren wanneer de klant vanaf de Mollie-betaalpagina terug navigeert naar de checkout. Dit helpt om gereserveerde voorraad sneller vrij te geven, maar moet alleen worden ingeschakeld als die afweging acceptabel is.

## Checklist Na De Upgrade

Gebruik deze checklist na de pakket-update om te bevestigen dat de winkel klaar is voor productiegebruik.

Controleer na de upgrade:

1. voer de zelftest uit
2. controleer de API-sleutels en de **Profile ID**
3. loop de capture-instellingen per betaalmethode na
4. controleer of `mollie.transaction.processor` is toegestaan en draait als queue-verwerking is ingeschakeld
5. controleer of automatische factuurcreatie ingeschakeld moet blijven
6. controleer eventuele headless-, PWA- of aangepaste webhookconfiguratie
7. plaats een echte checkouttest en controleer webhookverwerking, facturatie en orderstatus-updates
8. handel 2.x-orders die nog wachten op capture, annulering of refund af via het Mollie Dashboard

## Volgende Stappen

- [Installatie](INSTALLATION.md): Composer-updateprocedure
- [Configuratie](CONFIGURATION.md): Algemene configuratie na de upgrade
- [Orderbeheer](ORDER_MANAGEMENT.md): Capture, facturatie en refunds
- [Probleemoplossing](TROUBLESHOOTING.md): Diagnostiek voor queue, webhooks en compatibiliteit
