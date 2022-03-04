define([
    'mage/utils/wrapper',
    'uiRegistry',
    'Magento_Checkout/js/model/quote'
], function (
    wrapper,
    Registry,
    quote
) {
    'use strict';

    /**
     * When switching payment methods in the checkout we trigger a "save payment method" to calculate any
     * applicable payment fees. When the One Step Checkout module is active this replaces the selected payment method
     * with the previous version. So when you have iDeal selected, but select Credit Card, the module will still send
     * iDeal which causes the wrong payment fee to be displayed. This code changes the payment method to the correct
     * one.
     */
    return function (Component) {
        return Component.extend({
            collectParams: function () {
                var result = this._super();

                if (!result.paymentMethod ||
                    !result.paymentMethod.method ||
                    !quote.paymentMethod() ||
                    !quote.paymentMethod().method
                ) {
                    return result;
                }

                result.paymentMethod.method = quote.paymentMethod().method;

                return result;
            }
        })
    }
});
