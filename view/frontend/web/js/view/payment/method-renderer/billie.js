define(
    [
        'jquery',
        'underscore',
        'mage/translate',
        'ko',
        'mage/url',
        'mage/storage',
        'Mollie_Payment/js/view/payment/method-renderer/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Mollie_Payment/js/model/checkout-config',
        'jquery/jquery-storageapi'
    ],
    function (
        $,
        _,
        $t,
        ko,
        url,
        storage,
        Component,
        quote,
        checkoutData,
        customer,
        urlBuilder,
        checkoutConfigData
    ) {
        'use strict';

        var checkoutConfig = window.checkoutConfig.payment;

        return Component.extend(
            {
                validate() {
                    var billingAddress = quote.billingAddress();

                    if (!billingAddress || !billingAddress.company) {
                        this.messageContainer.addErrorMessage({
                            message: $t('Please enter a company name.')
                        });

                        return false;
                    }

                    return true;
                }
            }
        );
    }
);
