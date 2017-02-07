# Mollie Magento® 2 - BETA

As this is a Beta of the Mollie extension for Magento® 2, don't use this in production environments. 

## Installation

### Magento® Marketplace

This extension will be available on the Magento® Marketplace once it is out of beta. 

### Manually

1. Go to Magento® 2 root folder

2. Enter following commands to install module:

   ```
   composer require mollie/magento2
   ```

   Wait while dependencies are updated.

3. Enter following commands to enable module:

   ```
   php bin/magento module:enable Mollie_Payment
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. Enable and configure the Mollie extension in Magento® Admin under *Stores* >
   *Configuration* > *Sales* > *Payment Methods* > *Mollie*.
   
## Requirements

This extension requires the [Mollie API client for PHP.](https://github.com/mollie/mollie-api-php)

When using composer this will be installed automaticly.

To install manually, enter the following command in your Magento® 2 root folder:
```
composer require mollie/mollie-api-php
```

