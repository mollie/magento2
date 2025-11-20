/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'https://js.mollie.com/v2/mollie.js',
], function (ko, Component, $, url, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            elementId: "express-component-minicart",
            template: 'Mollie_Payment/checkout/minicart/express-components',
            sessionCreated: ko.observable(false),
            cartTotal: ko.observable(0),
            minicartIsOpen: ko.observable(false),
        },

        initialize: function () {
            this._super();

            this.cartTotal(customerData.get('cart')().subtotalAmount);
            customerData.get('cart').subscribe((cart) => {
                this.cartTotal(cart.subtotalAmount);
            });

            $("[data-block='minicart']").on('dropdowndialogopen', () => this.minicartIsOpen(true));
            $("[data-block='minicart']").on('dropdowndialogclose', () => this.minicartIsOpen(false));

            this.minicartIsOpen.subscribe(() => this.createSession());

            var oldPrice;
            this.cartTotal.subscribe(function (_oldPrice) {
                oldPrice = _oldPrice;
            }, this, 'beforeChange');

            this.cartTotal.subscribe((newPrice) => {
                document.getElementById(this.elementId).innerHTML = '';

                if (parseInt(newPrice) === 0) {
                    return;
                }

                if (parseInt(oldPrice) !== parseInt(newPrice)) {
                    this.sessionCreated(false);
                    this.createSession();
                }
            });
        },

        createSession() {
            if (this.sessionCreated() || !this.cartTotal() || !this.minicartIsOpen()) {
                return;
            }

            this.sessionCreated(true);
            $.ajax({
                global: false,
                context: this,
                type: 'POST',
                url: url.build('mollie/express/createSession'),
                success: function (result) {
                    this.initializeExpressComponent(result);
                }.bind(this),
            });
        },

        initializeExpressComponent: function (result) {
            const checkout = Mollie.Checkout(result.clientAccessToken)
            let configuration = {
                paymentMethods: {
                    idealcheckout: 'always',
                    applepay: 'never',
                    googlepay: 'never',
                }
            };

            const expressComponent = checkout.create('express-checkout', configuration);
            expressComponent.mount(document.getElementById(this.elementId))
        }
    })
})
