# Quickstart: Mollie Payments voor Magento 2

Deze handleiding leidt je in minder dan 15 minuten van een nieuwe installatie naar het verwerken van testbetalingen. Voor uitgebreidere configuratieopties, zie de andere artikelen in deze documentatie.

## Vereisten

Zorg voor het volgende voordat je begint:

- Magento **2.4.5 of hoger**
- PHP **8.1 of hoger**
- Een [Mollie-account](https://www.mollie.com/dashboard/signup) (gratis aan te maken)
- Composer geïnstalleerd op je server

## Stap 1: Installeer de extensie

Voer de volgende commando's uit vanuit je Magento-root:

```bash
composer require mollie/magento2
php bin/magento module:enable Mollie_Payment
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

Implementeer voor productieomgevingen ook statische content:

```bash
php bin/magento setup:static-content:deploy
```

## Stap 2: Haal je API-sleutels op

1. Log in op je [Mollie Dashboard](https://www.mollie.com/dashboard)
2. Ga naar **Developers**
3. Klik op **Create access token**
4. Voer een omschrijving in, selecteer **Standard API key**, selecteer je profiel en kies **Test**-modus
5. Kopieer de gegenereerde sleutel

## Stap 3: Voer je API-sleutel in Magento in

1. Ga in Magento Admin naar **Stores → Configuration → Mollie → General**
2. Vouw **Mollie Configuration** uit
3. Zet **Enabled** op **Yes**
4. Plak je **Test API key** in het veld **Test API Key**
5. Zet **Modus** op **Test**
6. Klik op **Save Config**
7. Leeg de cache: **System → Cache Management → Flush Magento Cache**

![Mollie Configuration-velden met Enabled, Modus, Test API Key, Live API Key en Profile ID](../images/api-keys-mollie-configuration.png)

Na het opslaan wordt het veld **Profile ID** automatisch ingevuld. Klik op **Test Apikey** om te bevestigen dat de sleutel door de Mollie API wordt geaccepteerd voordat je verdergaat.

![Profile ID-veld en de knoppen Test Apikey en Run Self-test](../images/quickstart-test-apikey.png)

## Stap 4: Schakel betaalmethoden in

1. Ga naar **Stores → Configuration → Mollie → Payment Methods**
2. Scroll naar de afzonderlijke secties voor betaalmethoden (iDEAL, Credit Card, enzovoort)
3. Zet **Enabled** op **Yes** voor elke methode die je wilt aanbieden
4. Stel indien gewenst de **Title** in die klanten te zien krijgen
5. Klik op **Save Config** en leeg de cache

**Tip:** Schakel eerst iDEAL of Credit Card in om te controleren of de configuratie werkt voordat je alle methoden inschakelt.

## Stap 5: Plaats een testbestelling

1. Ga naar je webshop en voeg een product toe aan de winkelwagen
2. Ga naar de kassa
3. Selecteer een Mollie-betaalmethode (bijvoorbeeld iDEAL)
4. Voltooi de betaling met Mollie's **testgegevens**:
   - Voor iDEAL: selecteer een willekeurige bank, elk bedrag
   - Voor creditcard: gebruik testkaartnummer `4543 4740 0224 9996`, vervaldatum `12/25`, CVV `123`
5. Je zou op de bevestigingspagina van de bestelling moeten belanden
6. Controleer in Magento Admin onder **Sales → Orders** of de orderstatus **Processing** is

## Stap 6: Voer de zelftest uit

Voer de ingebouwde zelftest uit voordat je live gaat om eventuele configuratieproblemen op te sporen. De zelftest controleert webhook-bereikbaarheid, wachtrij-instellingen, Apple Pay-domeinvalidatie en meer.

1. Ga naar **Stores → Configuration → Mollie → General**
2. Klik op **Run Self-test**

![Run Self-test-knop in de Mollie-configuratieheader](../images/quickstart-run-self-test.png)

Los eventuele fouten op voordat je verdergaat. Waarschuwingen zijn informatief maar het is de moeite waard ze door te nemen.

## Stap 7: Ga live

Wanneer je klaar bent om echte betalingen te accepteren:

1. Ga naar **Stores → Configuration → Mollie → General**
2. Vervang de Test API key door je **Live API key**
3. Zet **Modus** op **Live**
4. Klik op **Save Config** en leeg de cache
5. Klik op **Test Apikey** om te bevestigen dat de live-sleutel geldig is
6. Plaats een echte testbestelling met een klein bedrag om te bevestigen dat alles van begin tot eind werkt

**Belangrijk:** Zorg dat je webshop bereikbaar is via HTTPS. Mollie vereist een geldig SSL-certificaat om live betalingen te verwerken.

## Volgende stappen

- [API-sleutels](API_KEYS.md) - Configuratie van sleutels per store, test- versus live-omgevingen
- [Betaalmethoden](PAYMENT_METHODS.md) - Volledig overzicht van methoden en hun individuele instellingen
- [Configuratie](CONFIGURATION.md) - Alle algemene instellingen toegelicht
- [Orderbeheer](ORDER_MANAGEMENT.md) - Orderstatussen, facturering en terugbetalingen
- [Probleemoplossing](TROUBLESHOOTING.md) - Veelvoorkomende problemen en oplossingen
