/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var billieComponent = 'Mollie_Payment/js/view/payment/method-renderer/billie';
        var defaultComponent = 'Mollie_Payment/js/view/payment/method-renderer/default';
        var giftcardComponent = 'Mollie_Payment/js/view/payment/method-renderer/giftcard';
        var kbcComponent = 'Mollie_Payment/js/view/payment/method-renderer/kbc';
        var pointofsaleComponent = 'Mollie_Payment/js/view/payment/method-renderer/pointofsale';

        var creditcardComponent = 'Mollie_Payment/js/view/payment/method-renderer/creditcard';
        var checkoutConfig = window.checkoutConfig.payment.mollie;
        if (checkoutConfig.profile_id && checkoutConfig.creditcard.use_components) {
            creditcardComponent = 'Mollie_Payment/js/view/payment/method-renderer/creditcard-with-components';
        }

        var methods = [
            {type: 'mollie_methods_alma', component: defaultComponent},
            {type: 'mollie_methods_bancomatpay', component: defaultComponent},
            {type: 'mollie_methods_bancontact', component: defaultComponent},
            {type: 'mollie_methods_banktransfer', component: defaultComponent},
            {type: 'mollie_methods_belfius', component: defaultComponent},
            {type: 'mollie_methods_billie', component: billieComponent},
            {type: 'mollie_methods_bizum', component: defaultComponent},
            {type: 'mollie_methods_blik', component: defaultComponent},
            {type: 'mollie_methods_creditcard', component: creditcardComponent},
            {type: 'mollie_methods_directdebit', component: defaultComponent},
            {type: 'mollie_methods_eps', component: defaultComponent},
            {type: 'mollie_methods_giftcard', component: giftcardComponent},
            {type: 'mollie_methods_googlepay', component: defaultComponent},
            {type: 'mollie_methods_ideal', component: defaultComponent},
            {type: 'mollie_methods_in3', component: defaultComponent},
            {type: 'mollie_methods_kbc', component: kbcComponent},
            {type: 'mollie_methods_klarna', component: defaultComponent},
            {type: 'mollie_methods_klarnapaylater', component: defaultComponent},
            {type: 'mollie_methods_klarnapaynow', component: defaultComponent},
            {type: 'mollie_methods_klarnasliceit', component: defaultComponent},
            {type: 'mollie_methods_mbway', component: defaultComponent},
            {type: 'mollie_methods_mobilepay', component: defaultComponent},
            {type: 'mollie_methods_multibanco', component: defaultComponent},
            {type: 'mollie_methods_mybank', component: defaultComponent},
            {type: 'mollie_methods_paybybank', component: defaultComponent},
            {type: 'mollie_methods_paypal', component: defaultComponent},
            {type: 'mollie_methods_paysafecard', component: defaultComponent},
            {type: 'mollie_methods_pointofsale', component: pointofsaleComponent},
            {type: 'mollie_methods_payconiq', component: defaultComponent},
            {type: 'mollie_methods_przelewy24', component: defaultComponent},
            {type: 'mollie_methods_riverty', component: defaultComponent},
            {type: 'mollie_methods_satispay', component: defaultComponent},
            {type: 'mollie_methods_sofort', component: defaultComponent},
            {type: 'mollie_methods_swish', component: defaultComponent},
            {type: 'mollie_methods_trustly', component: defaultComponent},
            {type: 'mollie_methods_twint', component: defaultComponent},
            {type: 'mollie_methods_vipps', component: defaultComponent},
            {type: 'mollie_methods_voucher', component: defaultComponent}
        ];

        function canUseApplePay()
        {
            try {
                return window.ApplePaySession && window.ApplePaySession.canMakePayments();
            } catch (error) {
                console.warn('Error when trying to check Apple Pay:', error);
                return false;
            }
        }

        /**
         * Only add Apple Pay if the current client supports Apple Pay.
         */
        if (canUseApplePay()) {
            var applePayComponent = defaultComponent;
            if (checkoutConfig.applepay.integration_type === 'direct') {
                applePayComponent = 'Mollie_Payment/js/view/payment/method-renderer/applepay-direct';
            }

            methods.push({
                type: 'mollie_methods_applepay',
                component: applePayComponent
            });
        }

        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);
