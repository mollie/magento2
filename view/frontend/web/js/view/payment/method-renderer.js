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
        var defaultComponent = 'Mollie_Payment/js/view/payment/method-renderer/default';
        var idealComponent = 'Mollie_Payment/js/view/payment/method-renderer/ideal';
        var giftcardComponent = 'Mollie_Payment/js/view/payment/method-renderer/giftcard';
        var methods = [
            {type: 'mollie_methods_bancontact', component: defaultComponent},
            {type: 'mollie_methods_banktransfer', component: defaultComponent},
            {type: 'mollie_methods_belfius', component: defaultComponent},
            {type: 'mollie_methods_bitcoin', component: defaultComponent},
            {type: 'mollie_methods_creditcard', component: defaultComponent},
            {type: 'mollie_methods_ideal', component: idealComponent},
            {type: 'mollie_methods_kbc', component: defaultComponent},
            {type: 'mollie_methods_paypal', component: defaultComponent},
            {type: 'mollie_methods_paysafecard', component: defaultComponent},
            {type: 'mollie_methods_sofort', component: defaultComponent},
            {type: 'mollie_methods_giftcard', component: giftcardComponent}
        ];
        $.each(methods, function (k, method) {
            if (window.checkoutConfig.payment.isActive[method['type']]) {
                rendererList.push(method);
            }
        });

        return Component.extend({});
    }
);