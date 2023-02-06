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
bin/magento config:set payment/mollie_methods_creditcard/active 1 &
bin/magento config:set payment/mollie_methods_giftcard/active 1 &
bin/magento config:set payment/mollie_methods_ideal/active 1 &
bin/magento config:set payment/mollie_methods_kbc/active 1 &
bin/magento config:set payment/mollie_methods_klarnasliceit/active 1 &
bin/magento config:set payment/mollie_methods_paypal/active 1 &
bin/magento config:set payment/mollie_methods_przelewy24/active 1 &
bin/magento config:set payment/mollie_methods_bancontact/active 1 &
bin/magento config:set payment/mollie_methods_belfius/active 1 &
bin/magento config:set payment/mollie_methods_eps/active 1 &
bin/magento config:set payment/mollie_methods_giropay/active 1 &
bin/magento config:set payment/mollie_methods_klarnapaylater/active 1 &
bin/magento config:set payment/mollie_methods_paymentlink/active 1 &
bin/magento config:set payment/mollie_methods_paysafecard/active 1 &
bin/magento config:set payment/mollie_methods_sofort/active 1 &

# Enable Components
bin/magento config:set payment/mollie_methods_creditcard/use_components 1 &

# Enable QR
bin/magento config:set payment/mollie_methods_ideal/add_qr 1 &

bin/magento config:set payment/mollie_general/use_webhooks disabled &

wait

# Flush config
bin/magento cache:flush config
