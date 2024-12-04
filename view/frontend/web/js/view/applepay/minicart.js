define([
    'jquery',
    'Magento_Customer/js/customer-data',
    '../product/apple-pay-button',
    'mage/url'
], function (
    $,
    customerData,
    Component,
    url
) {
    'use strict';

    return Component.extend({
        defaults: {
            grandTotalAmount: 0,
            storeName: null,
            shippingMethods: null,
            selectedShippingMethod: null,
            quoteTotals: null,
            countryCode: null,
            postalCode: null,
        },

        initialize: function () {
            this._super();

            var element = document.getElementById('mollie_applepay_minicart');

            if (this.canUseApplePay()) {
                element.classList.remove('mollie-applepay-button-hidden');

                element.addEventListener('click', this.makePayment.bind(this));
            }

            return this;
        },

        makePayment: function () {
            var amount = this.grandTotalAmount;

            var request = {
                countryCode: this.storeCountry,
                currencyCode: this.storeCurrency,
                supportedNetworks: this.supportedNetworks,
                merchantCapabilities: ['supports3DS'],
                total: {
                    label: this.storeName,
                    amount: amount
                },
                shippingType: 'shipping',
                requiredBillingContactFields: [
                    'postalAddress',
                    'name',
                    'email',
                    'phone'
                ],
                requiredShippingContactFields: [
                    'postalAddress',
                    'name',
                    'email',
                    'phone'
                ]
            }

            if (!this.session) {
                this.session = new ApplePaySession(3, request);
            }

            this.session.onpaymentmethodselected = function () {
                this.session.completePaymentMethodSelection(this.getTotal(), []);
            }.bind(this);

            this.session.onshippingcontactselected = function (event) {
                this.countryCode = event.shippingContact.countryCode;
                this.postalCode = event.shippingContact.postalCode;

                $.ajax({
                    global: false,
                    context: this,
                    type: 'POST',
                    url: url.build('mollie/applePay/shippingMethods'),
                    data: {
                        countryCode: event.shippingContact.countryCode,
                        postalCode: event.shippingContact.postalCode
                    },
                    success: function (result) {
                        this.shippingMethods = result.shipping_methods;
                        this.selectedShippingMethod = result.shipping_methods[0];
                        this.quoteTotals = result.totals;

                        this.session.completeShippingContactSelection(
                            ApplePaySession.STATUS_SUCCESS,
                            result.shipping_methods,
                            this.getTotal(),
                            this.getLineItems()
                        );
                    }.bind(this),
                    error: this.handleAjaxError
                })
            }.bind(this);

            this.session.onshippingmethodselected = function (event) {
                this.selectedShippingMethod = event.shippingMethod;

                $.ajax({
                    global: false,
                    context: this,
                    type: 'POST',
                    url: url.build('mollie/applePay/shippingMethods'),
                    data: {
                        shippingMethod: this.selectedShippingMethod,
                        countryCode: this.countryCode,
                        postalCode: this.postalCode
                    },
                    success: function (result) {
                        this.quoteTotals = result.totals;

                        this.session.completeShippingMethodSelection(
                            ApplePaySession.STATUS_SUCCESS,
                            this.getTotal(),
                            this.getLineItems()
                        )
                    }.bind(this),
                    error: this.handleAjaxError
                });
            }.bind(this);

            this.session.onpaymentauthorized = function (event) {
                $.ajax({
                    global: false,
                    context: this,
                    type: 'POST',
                    url: url.build('mollie/applePay/placeOrder'),
                    data: {
                        shippingMethod: this.selectedShippingMethod,
                        billingAddress: event.payment.billingContact,
                        shippingAddress: event.payment.shippingContact,
                        applePayPaymentToken: JSON.stringify(event.payment.token)
                    },
                    success: function (result) {
                        if (!this.session) {
                            console.warn('Payment canceled');
                            return;
                        }

                        if (result.error) {
                            this.sendMessage(result.error_message);
                            this.session.abort()
                            return;
                        }

                        if (!result.url) {
                            this.sendMessage('Something went wrong, please try again later.');
                            this.session.abort()
                            return;
                        }

                        this.session.completePayment(ApplePaySession.STATUS_SUCCESS);

                        customerData.invalidate(['cart']);

                        setTimeout( function () {
                            location.href = result.url
                        }, 1000);
                    }.bind(this),
                    error: this.handleAjaxError
                })
            }.bind(this);

            this.session.onvalidatemerchant = function (event) {
                $.ajax({
                    type: 'POST',
                    url: url.build('mollie/checkout/applePayValidation'),
                    data: {
                        validationURL: event.validationURL
                    },
                    success: function (result) {
                        this.session.completeMerchantValidation(result);
                    }.bind(this)
                })
            }.bind(this);

            this.session.oncancel = function () {
                this.session = null;
            }.bind(this);

            this.session.begin();
        },

        sendMessage: function (message) {
            var customerMessages = customerData.get('messages')() || {},
                messages = customerMessages.messages || [];

            messages.push({
                text: message,
                type: 'error'
            });

            customerMessages.messages = messages;
            customerData.set('messages', customerMessages);
        }
    });
});
