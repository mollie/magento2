/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'https://js.mollie.com/v2/mollie.js',
], function (
    $,
    Component,
    url,
    quote,
    resourceUrlManager,
) {
    let sessionCreated = false;
    const enabled = window.checkoutConfig.payment.mollie?.expresscomponents?.enabled ?? true;

    return Component.extend({
        defaults: {
            isEnabled: enabled,
            placement: null,
        },

        createSession() {
            if (sessionCreated) {
                return;
            }

            let interval = setInterval( function () {
                let data = {};
                if (resourceUrlManager.getCheckoutMethod() === 'guest' && !quote.guestEmail) {
                    return;
                }

                if (resourceUrlManager.getCheckoutMethod() === 'guest') {
                    data = {
                        email: quote.guestEmail,
                    };
                }

                clearInterval(interval);
                sessionCreated = true;

                $.ajax({
                    global: false,
                    context: this,
                    type: 'POST',
                    data: data,
                    url: url.build('mollie/express/createSession'),
                    success: function (result) {
                        this.initializeExpressComponent(result);
                    }.bind(this),
                });
            }.bind(this), 1000);
        },

        initializeExpressComponent: function (result) {
            const checkout = Mollie.Checkout(result.clientAccessToken)
            let configuration = {};

            if (this.placement === 'cart') {
                configuration = {
                    paymentMethods: {
                        idealcheckout: 'always',
                        applepay: 'never',
                        googlepay: 'never',
                    }
                };
            }

            if (this.placement === 'checkout') {
                configuration = {
                    paymentMethods: {
                        idealcheckout: 'never',
                    }
                };
            }

            const expressComponent = checkout.create('express-checkout', configuration);
            expressComponent.mount(document.getElementById("express-component"))
        }
    })
})
