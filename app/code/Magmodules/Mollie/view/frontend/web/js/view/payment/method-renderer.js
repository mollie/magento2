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

        var defaultComponent = 'Magmodules_Mollie/js/view/payment/method-renderer/default';
        var methods = [
            {type: 'mollie_methods_bancontact', component: defaultComponent},
            {type: 'mollie_methods_banktransfer', component: defaultComponent},
            {type: 'mollie_methods_belfius', component: defaultComponent},
            {type: 'mollie_methods_bitcoin', component: defaultComponent},
            {type: 'mollie_methods_creditcard', component: defaultComponent},
            {type: 'mollie_methods_ideal', component: defaultComponent},
            {type: 'mollie_methods_kbc', component: defaultComponent},
            {type: 'mollie_methods_paypal', component: defaultComponent},
            {type: 'mollie_methods_paysafecard', component: defaultComponent},
            {type: 'mollie_methods_sofort', component: defaultComponent}
        ];
        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);