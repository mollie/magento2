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

## Een pull request installeren

Als een oplossing voor je probleem al in een open pull request staat maar nog niet is uitgebracht, kun je die pull request als patch op je winkel toepassen. Dit is de snelste manier om te bevestigen dat de oplossing werkt voor jouw installatie.

**Belangrijk:** Een patch is tijdelijk. Verwijder de patch zodra de oplossing in een release zit, want het bijwerken van de extensie mislukt zodra de gepatchte code niet meer overeenkomt.

### 1. Installeer de patches-plugin

```bash
composer require cweagans/composer-patches
```

Composer vraagt of je de plugin vertrouwt om code uit te voeren. Antwoord met `y` en druk op Enter, anders wordt er nooit een patch toegepast.

### 2. Download het patchbestand

Elke pull request heeft een patchweergave: neem de URL van de pull request op [github.com/mollie/magento2/pulls](https://github.com/mollie/magento2/pulls) en voeg `.patch` toe. Sla het resultaat op in een map `patches` in je Magento-root.

```bash
mkdir -p patches
curl -L -o patches/1234.patch https://github.com/mollie/magento2/pull/1234.patch
```

Bewaar het bestand lokaal in plaats van de externe URL in `composer.json` te zetten. Een externe patch wordt bij elke deployment opnieuw gedownload, waardoor de inhoud kan wijzigen nadat je die hebt gecontroleerd.

### 3. Verwijs naar de patch in composer.json

Voeg de patch toe aan de sectie `extra` van `composer.json`:

```json
{
    "extra": {
        "patches": {
            "mollie/magento2": {
                "Oplossing voor issue #1234": "patches/1234.patch"
            }
        }
    }
}
```

De sleutel is een vrije omschrijving die Composer toont bij het toepassen van de patch. Gebruik die om vast te leggen wat de patch oplost.

### 4. Pas de patch toe

```bash
composer update mollie/magento2
```

Composer installeert het pakket opnieuw en meldt elke toegepaste patch. Sluit af met de gebruikelijke stappen na installatie:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### De patch verwijderen

Verwijder het item uit de sectie `patches` in `composer.json` en voer opnieuw `composer update mollie/magento2` uit. Het pakket wordt teruggezet naar de uitgebrachte versie.

## Composer meldt een hogere versie op Packagist

Composer kan de installatie weigeren met een melding als:

```
Higher matching version 2.40.0 of mollie/magento2 was found in public repository
packagist.org than 2.39.0 in private https://repo.magento.com
```

De extensie wordt zowel op Packagist als op de Adobe Commerce Marketplace gepubliceerd. Releases op de Marketplace doorlopen een beoordelingsproces dat tijd kost, waardoor een nieuwe versie eerst op Packagist beschikbaar is. Composer kiest niet stilzwijgend voor de publieke versie, omdat dat ruimte geeft aan een dependency confusion-aanval, waarbij iemand een pakket onder een privé-vendornaam op een publieke repository publiceert.

Beide pakketten zijn dezelfde extensie. Kies een van de onderstaande oplossingen.

### Sluit Mollie uit van repo.magento.com (aanbevolen)

Laat Composer Mollie-pakketten nooit via de Marketplace-repository oplossen. Voeg `exclude` toe aan het item `repo.magento.com` in de sectie `repositories` van `composer.json`:

```json
{
    "repositories": {
        "repo.magento.com": {
            "type": "composer",
            "url": "https://repo.magento.com/",
            "exclude": ["mollie/*"]
        }
    }
}
```

Voer daarna opnieuw `composer require mollie/magento2` uit. Dit is een permanente oplossing: latere updates verlopen via Packagist zonder dat de melding terugkeert.

### Installeer de versie van de Marketplace

Vraag exact de versie op die de melding noemt voor `repo.magento.com`:

```bash
composer require mollie/magento2:2.39.0
```

Je blijft dan op een oudere release en krijgt bij de volgende update dezelfde melding. Gebruik dit alleen als je deploymentproces vereist dat elk pakket van `repo.magento.com` komt.

### Verwijder repo.magento.com tijdelijk

Haal het item `repo.magento.com` uit de sectie `repositories`, voer `composer require mollie/magento2` uit en zet het item daarna terug. De opgeloste versie wordt vastgelegd in `composer.lock`, dus de installatie slaagt, maar de melding komt terug zodra je de extensie de volgende keer bijwerkt.

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
