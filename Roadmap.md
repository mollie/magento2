# Mollie & Magento Roadmap

This is an overview of the features we are looking to work on over the next few months and a an overview of features that are ready for beta testing. 

This is a living doc that will evolve as priorities grow and shift. Please feel free to file issues on this repository if you have questions, concerns, or suggestions.

## What is ready for beta testing

* **Mollie Components:** 
[Mollie Components](https://docs.mollie.com/guides/mollie-components/overview) is a set of Javascript APIs that allow you to add the fields needed for credit card holder data to your own checkout, in a way that is fully PCI-DSS SAQ-A compliant.
Branche: https://github.com/mollie/magento2/tree/1.10.0-pwa-components

* **PWA integration:** 
To integrate the Mollie extension with a PWA, we added 2 endpoints to the normal Magento 2 checkout flow, see [manual](https://github.com/mollie/magento2/wiki/PWA-integration).

## What we're working on / what is on our backlog

* **Surcharge for all different payment methods:** 
Due to the new EU law only Klarna and PayPal can be surcharged to a certain amount. However, as Mollie Payemts is also used for B2B transactions and used outside of the EU we are planning to open the payment surcharge for all method in an upcoming release.
Open Issue: https://github.com/mollie/magento2/issues/211

* **Direct integration of Apple Pay:** 
Adding [direct integration of Apple Pay](https://docs.mollie.com/guides/applepay-direct-integration) into Magento 2 right from the product page. 
Open Issue: https://github.com/mollie/magento2/issues/214

* **Basic Second Chance emails:** 
Adding an option to the admin to send failed or unfinished payments a second chance email with a payment link to revive the order. This email will be sent from Magento and will be fully customizable. Emails can be sent manually, but we will add options to automate this in a future version.
Open Issue: https://github.com/mollie/magento2/issues/212

