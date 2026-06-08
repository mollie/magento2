# Installatie: Mollie Payments voor Magento 2

Dit artikel is bedoeld voor ontwikkelaars en systeembeheerders die Mollie Payments voor Magento 2 installeren, bijwerken of controleren. Voor een beknopte stapsgewijze uitleg die direct naar het plaatsen van een testbetaling gaat, zie [Quickstart](QUICKSTART.md).

## Systeemvereisten

Controleer voordat je installeert of de omgeving aan de volgende vereisten voldoet:

- Magento Open Source of Adobe Commerce **2.4.5** of hoger
- PHP **8.1** of hoger
- Composer **2.x**
- PHP-extensie `ext-json`

## Installatie via Composer

### 1. Voeg het pakket toe

Voer het volgende commando uit vanuit de Magento-rootmap:

```bash
composer require mollie/magento2
```

Composer lost het pakket op en downloadt het samen met de afhankelijkheid `mollie/mollie-api-php`.

### 2. Schakel de module in

```bash
php bin/magento module:enable Mollie_Payment
```

### 3. Voer de upgrade-scripts uit

```bash
php bin/magento setup:upgrade
```

### 4. Compileer dependency injection

```bash
php bin/magento setup:di:compile
```

### 5. Implementeer statische content

Vereist voor productiemodus. Sla deze stap over bij installaties in ontwikkelaarsmodus.

```bash
php bin/magento setup:static-content:deploy
```

### 6. Leeg de cache

```bash
php bin/magento cache:flush
```

## Controleer de installatie

Nadat je de bovenstaande stappen hebt voltooid, bevestig je dat de module actief is:

```bash
php bin/magento module:status Mollie_Payment
```

De uitvoer moet `Module is enabled` bevatten.

Controleer de geïnstalleerde versie:

```bash
composer show mollie/magento2 | grep versions
```

Ga in Magento Admin naar **System → Web Setup Wizard → Component Manager** (of **System → Manage Extensions** op Adobe Commerce Cloud) om te bevestigen dat `mollie/magento2` verschijnt met de juiste versie.

## Installatie via Magento Marketplace

De extensie staat ook vermeld op de [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com/mollie-magento2.html). De installatie gebruikt nog steeds Composer - de Marketplace is een ontdekkings- en licentiemechanisme, geen apart implementatiepad.

Als je Magento Marketplace-authenticatiesleutels hebt (beschikbaar via je Marketplace-account onder **Access Keys**), stel je deze in `auth.json` in de Magento-root in voordat je Composer uitvoert:

```json
{
    "http-basic": {
        "repo.magento.com": {
            "username": "<public key>",
            "password": "<private key>"
        }
    }
}
```

Volg daarna de bovenstaande stappen voor [Installatie via Composer](#installatie-via-composer). De pakketnaam en alle vervolgcommando's zijn identiek.

## Een bestaande installatie bijwerken

Als je upgrade vanaf een oudere major release, lees dan eerst [Upgraden](UPGRADING.md) voordat je de Composer-update uitvoert.

### 1. Werk het pakket bij

```bash
composer update mollie/magento2
```

Bijwerken naar een specifieke versie:

```bash
composer require mollie/magento2:<version>
```

### 2. Voer de upgrade- en compilatiestappen uit

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

Bekijk [Upgraden](UPGRADING.md) en de [changelog](https://github.com/mollie/magento2/releases) voordat je bijwerkt. Hoofdversies kunnen breaking changes bevatten die configuratieaanpassingen vereisen.

## Aanvullende modules

De volgende pakketten breiden de standaardfunctionaliteit van de extensie uit. Elk pakket wordt afzonderlijk via Composer geïnstalleerd met dezelfde stappen als hierboven.

| Pakket | Doel |
|---|---|
| [`mollie/magento2-hyva-compatibility`](https://github.com/mollie/magento2-hyva-compatibility) | Hyvä Theme-compatibiliteit |
| [`mollie/magento2-hyva-checkout`](https://github.com/mollie/magento2-hyva-checkout) | Hyvä Checkout-integratie |
| [`mollie/magento2-hyva-react-checkout`](https://github.com/mollie/magento2-hyva-react-checkout) | Hyvä React Checkout-integratie |
| [`mollie/magento2-multishipping`](https://github.com/mollie/magento2-multishipping) | Ondersteuning voor meervoudige verzending |
| [`mollie/magento2-subscriptions`](https://github.com/mollie/magento2-subscriptions) | Abonnementsbetalingen |

## Volgende stappen

- [Configuratie](CONFIGURATION.md): Alle algemene instellingen
- [API-sleutels](API_KEYS.md): Je Mollie-account koppelen
- [Betaalmethoden](PAYMENT_METHODS.md): Afzonderlijke methoden inschakelen en configureren
- [Probleemoplossing](TROUBLESHOOTING.md): Veelvoorkomende installatieproblemen
