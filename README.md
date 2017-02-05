# Mollie Magento 2 - BETA

Beta version Mollie for Magento 2, not for production environments. 

## Getting Started

Download the extension as a ZIP file from this repository or install our module with [Composer](https://getcomposer.org/) using the following command:

```
composer require mollie/Magento2
```

If you're installing the extension manually, unzip the archive and upload the contents of the /src directory to `/app/code/Mollie/Payment`. 

After uploading, run the following commands:

```
bin/magento module:enable Mollie_Payment
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Requirements

This extension requires the [Mollie API client for PHP](https://github.com/mollie/mollie-api-php) 

Run the following command in your Magento 2 store root:
```
composer require mollie/mollie-api-php
```

