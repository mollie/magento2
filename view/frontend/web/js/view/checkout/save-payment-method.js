define([
    'uiComponent',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/action/get-totals'
], function (
    Component,
    storage,
    quote,
    resourceUrlManager,
    totals,
    reloadTotals
) {
    var isApplicableMethod = function (method) {
        return method === 'mollie_methods_klarnapaylater' || method === 'mollie_methods_klarnasliceit';
    };

    return Component.extend({
        initialize: function () {
            this._super();

            var oldMethod;
            quote.paymentMethod.subscribe( function (oldValue) {
                if (oldValue && oldValue.method) {
                    oldMethod = oldValue.method;
                }
            }, this, 'beforeChange');

            quote.paymentMethod.subscribe( function (newValue) {
                // If the old method was a payment fee method we also need to update
                if (isApplicableMethod(oldMethod) || isApplicableMethod(newValue.method)) {
                    this.savePaymentMethod(newValue.method);
                }
            }.bind(this));
        },

        savePaymentMethod: function (method) {
            var params = {};
            var payload = {};

            if (resourceUrlManager.getCheckoutMethod() === 'guest') {
                params = {
                    cartId: quote.getQuoteId()
                };
                payload.email = quote.guestEmail;
            }

            var urls = {
                'guest': '/guest-carts/:cartId/set-payment-information',
                'customer': '/carts/mine/set-payment-information'
            };
            var url = resourceUrlManager.getUrl(urls, params);

            payload.paymentMethod = {
                method: method
            };

            payload.billingAddress = quote.billingAddress();

            totals.isLoading(true);
            storage.post(
                url,
                JSON.stringify(payload)
            ).done( function () {
                reloadTotals([]);
                totals.isLoading(false);
            }).error( function () {
                totals.isLoading(false);
            });
        }
    });
});
