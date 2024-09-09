#
# Copyright Magmodules.eu. All rights reserved.
# See COPYING.txt for license details.
#

if [ -z "$MOLLIE_API_KEY_TEST" ]; then
    echo "Variable \$MOLLIE_API_KEY_TEST is not set"
    exit 1
fi

# General configuration
bin/magento config:set payment/mollie_general/profileid pfl_8yCABHRz37 &
magerun2 config:store:set payment/mollie_general/apikey_test $MOLLIE_API_KEY_TEST --encrypt &
bin/magento config:set payment/mollie_general/enabled 1 &
bin/magento config:set payment/mollie_general/type test &

# Enable all payment methods
bin/magento config:set payment/mollie_methods_applepay/active 1 &
bin/magento config:set payment/mollie_methods_banktransfer/active 1 &
bin/magento config:set payment/mollie_methods_billie/active 1 &
bin/magento config:set payment/mollie_methods_blik/active 1 &
bin/magento config:set payment/mollie_methods_creditcard/active 1 &
bin/magento config:set payment/mollie_methods_giftcard/active 1 &
bin/magento config:set payment/mollie_methods_ideal/active 1 &
bin/magento config:set payment/mollie_methods_kbc/active 1 &
bin/magento config:set payment/mollie_methods_klarnasliceit/active 1 &
bin/magento config:set payment/mollie_methods_paypal/active 1 &
bin/magento config:set payment/mollie_methods_przelewy24/active 1 &
bin/magento config:set payment/mollie_methods_alma/active 1 &
bin/magento config:set payment/mollie_methods_bancontact/active 1 &
bin/magento config:set payment/mollie_methods_bancomatpay/active 1 &
bin/magento config:set payment/mollie_methods_belfius/active 1 &
bin/magento config:set payment/mollie_methods_eps/active 1 &
bin/magento config:set payment/mollie_methods_klarnapaylater/active 1 &
bin/magento config:set payment/mollie_methods_paymentlink/active 1 &
bin/magento config:set payment/mollie_methods_paysafecard/active 1 &
bin/magento config:set payment/mollie_methods_pointofsale/active 1 &
bin/magento config:set payment/mollie_methods_riverty/active 1 &
bin/magento config:set payment/mollie_methods_satispay/active 1 &
bin/magento config:set payment/mollie_methods_sofort/active 1 &
bin/magento config:set payment/mollie_methods_trustly/active 1 &
bin/magento config:set payment/mollie_methods_twint/active 1 &

# Enable Components
bin/magento config:set payment/mollie_methods_creditcard/use_components 1 &

# Enable QR
bin/magento config:set payment/mollie_methods_ideal/add_qr 1 &

# Disable webhooks
bin/magento config:set payment/mollie_general/use_webhooks disabled &

# Configure currency for the swiss store view
bin/magento config:set currency/options/allow EUR,CHF,PLN &

# Swiss scope
bin/magento config:set currency/options/default CHF --scope=ch &
bin/magento config:set payment/mollie_general/currency 0 --scope=ch &

# Polish scope
bin/magento config:set currency/options/default PLN --scope=pl &
bin/magento config:set payment/mollie_general/currency 0 --scope=pl &

# Disable the use of the base currency
bin/magento config:set payment/mollie_general/currency 0 &

# Insert rates, otherwise the currency switcher won't show
magerun2 db:query 'INSERT INTO `directory_currency_rate` (`currency_from`, `currency_to`, `rate`) VALUES ("EUR", "PLN", 1.0);' &
magerun2 db:query 'INSERT INTO `directory_currency_rate` (`currency_from`, `currency_to`, `rate`) VALUES ("EUR", "CHF", 1.0);' &

wait

if grep -q Magento_TwoFactorAuth "app/etc/config.php"; then
    ./retry "php bin/magento module:disable Magento_TwoFactorAuth -f"
fi


# Flush config
bin/magento cache:flush config
