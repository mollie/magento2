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
    'use strict';

    const enabled = window.checkoutConfig.payment.mollie?.expresscomponents?.enabled ?? true;
    const PLACEMENT_CHECKOUT = 'checkout';

    return Component.extend({
        defaults: {
            isEnabled: enabled,
            placement: null,
            elementId: 'express-component',
        },

        initialize: function () {
            this._super();

            this.requestedAmount = null;
            this.hasPendingRequest = false;

            quote.totals.subscribe(() => this.createSession());
        },

        createSession: function () {
            if (this.hasPendingRequest || !this.canCreateSession()) {
                return;
            }

            const amount = this.getSessionAmount();
            if (amount === null || amount === this.requestedAmount) {
                return;
            }

            this.requestedAmount = amount;
            this.hasPendingRequest = true;

            $.ajax({
                global: false,
                context: this,
                type: 'POST',
                data: this.getRequestData(),
                url: this.getCreateSessionUrl(),
                success: function (result) {
                    this.initializeExpressComponent(result);
                }.bind(this),
                error: function () {
                    this.requestedAmount = null;
                }.bind(this),
                complete: function () {
                    this.hasPendingRequest = false;
                }.bind(this),
            });
        },

        canCreateSession: function () {
            if (!this.isEnabled) {
                return false;
            }

            if (this.isGuest() && !quote.guestEmail) {
                return false;
            }

            return !this.isWaitingForShippingMethod();
        },

        /*
         * In the checkout the session amount is the grand total, which only includes the shipping costs after the
         * customer has selected a shipping method. Creating the session before that charges the subtotal only.
         */
        isWaitingForShippingMethod: function () {
            return this.isCheckoutPlacement() && !quote.isVirtual() && !quote.shippingMethod();
        },

        getSessionAmount: function () {
            const totals = quote.totals();
            if (!totals) {
                return null;
            }

            const amount = this.isCheckoutPlacement() ? totals.grand_total : totals.subtotal_incl_tax;

            return amount === undefined ? null : amount;
        },

        getCreateSessionUrl: function () {
            if (!this.placement) {
                return url.build('mollie/express/createSession');
            }

            return url.build('mollie/express/createSession/type/' + this.placement);
        },

        getRequestData: function () {
            return this.isGuest() ? {email: quote.guestEmail} : {};
        },

        isGuest: function () {
            return resourceUrlManager.getCheckoutMethod() === 'guest';
        },

        isCheckoutPlacement: function () {
            return this.placement === PLACEMENT_CHECKOUT;
        },

        initializeExpressComponent: function (result) {
            const container = document.getElementById(this.elementId);
            if (!container) {
                return;
            }

            container.innerHTML = '';

            const checkout = Mollie.Checkout(result.clientAccessToken)
            let configuration = {
                buttons: {
                    paypal: {
                        visibility: 'hidden',
                    }
                }
            };

            if (!this.isCheckoutPlacement()) {
                configuration.paymentMethods = {
                    applepay: 'never',
                    googlepay: 'never',
                };
            }

            const expressComponent = checkout.create('express-component', configuration);
            expressComponent.mount(container)
        }
    })
})
