/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'mage/url',
], function (customer, urlBuilder, storage, url) {
    return {
        paymentToken: '',

        placeOrder: function (method, originalPlaceOrder, data, event) {
            method.isPlaceOrderActionAllowed(false);
            this.beforePlaceOrder().always(function () {
                method.isPlaceOrderActionAllowed(true);
                originalPlaceOrder(data, event);
            }.bind(this));
        },

        beforePlaceOrder: function () {
            var serviceUrl;

            /**
             * We retrieve a payment token. This is used to start the transaction once the order is placed.
             */
            if (customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/carts/mine/mollie/payment-token', {});
            } else {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/mollie/payment-token', {
                    quoteId: quote.getQuoteId()
                });
            }

            var promise = storage.get(serviceUrl);

            promise.done( function (result) {
                this.paymentToken = result;
            }.bind(this));

            return promise;
        },

        afterPlaceOrder: function () {
            window.location = url.build('mollie/checkout/redirect/paymentToken/' + this.paymentToken);
        }
    }
})
