/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/action/get-totals'
], function (
    ko,
    Component,
    storage,
    quote,
    resourceUrlManager,
    totals,
    reloadTotals
) {
    var isApplicableMethod = function (method) {
        return method && method.substring(0, 6) === 'mollie';
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
                if (!newValue) {
                    return;
                }

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
                method: method,
                extension_attributes: {}
            };

            // Do not send the billing address, this is saved by Magento itself
            // payload.billingAddress = quote.billingAddress();

            /**
             * Problem: We need to set the payment method, therefor we created this function. The api call requires
             * that the agreements are all agreed by before doing any action. That's why we list all agreement ids
             * and sent them with the request. In a later point in the checkout this will also be checked.
             */
            var config = window.checkoutConfig.checkoutAgreements;
            if (config && config.isEnabled) {
                var ids = config.agreements.map( function (agreement) {
                    return agreement.agreementId;
                });

                payload.paymentMethod.extension_attributes.agreement_ids = ids;
            }

            totals.isLoading(true);
            storage.post(
                url,
                JSON.stringify(payload)
            ).done( function () {
                reloadTotals([]);
                totals.isLoading(false);
            }).fail( function () {
                totals.isLoading(false);
            });
        }
    });
});
