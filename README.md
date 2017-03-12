# Mollie Magento® 2

As this is the Mollie extension for Magento® 2. This extension has not been battle tested yet, although we've received many successful reports of clients that are running this extention without any problems.

## Installation

### Magento® Marketplace

This extension will also be available on the Magento® Marketplace when approved.

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

4. If Magento® is running in production mode, deploy static content: 

   ```
   php bin/magento setup:static-content:deploy
   ```

5. Enable and configure the Mollie extension in Magento® Admin under *Stores* >
   *Configuration* > *Sales* > *Payment Methods* > *Mollie*.

## Requirements

This extension requires the [Mollie API client for PHP.](https://github.com/mollie/mollie-api-php)

When using composer this will be installed automatically.

To install manually, enter the following command in your Magento® 2 root folder:
```
composer require mollie/mollie-api-php
```
