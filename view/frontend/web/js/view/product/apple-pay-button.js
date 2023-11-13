/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'mage/url',
    'mage/translate',
    'jquery',
    'Magento_Customer/js/customer-data'
], function (
    Component,
    url,
    __,
    $,
    customerData
) {
    return Component.extend({
        cartId: null,
        shippingMethods: [],
        selectedShippingMethod: null,
        session: null,
        totals: [],
        postalCode: null,

        // Variables that are set from the template: view/frontend/templates/product/view/applepay.phtml
        formSelector: null,
        currencyCode: null,
        countryCode: null,
        productName: null,
        storeName: null,
        supportedNetworks: [],

        initObservable: function () {
            this._super().observe([
                'applePayPaymentToken'
            ]);

            return this;
        },

        payWithApplePay: function () {
            var validator = $(this.formSelector).mage('validation');

            if (validator.validation('isValid')) {
                this.createApplePaySession();
            }
        },

        getProductPrice: function () {
            var price = $('[data-role=priceBox][data-product-id] [data-price-type="finalPrice"] .price')
                .html()
                .replace(/[^\d,.-]/g, '');

            // We get the price formatted as in the currency, eg 1.000,00 or 1,000.00. So remove all dots and
            // commas and divide by 100 to get the price in cents.
            return (price.replace(',', '').replace('.', '') / 100).toFixed(2);
        },

        createApplePaySession: function () {
            var request = {
                countryCode: this.countryCode,
                currencyCode: this.currencyCode,
                supportedNetworks: this.supportedNetworks,
                merchantCapabilities: ['supports3DS'],
                total: {
                    type: 'final',
                    label: this.storeName,
                    amount: this.getProductPrice(),
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
                this.session = new ApplePaySession(10, request);
            }

            this.session.onvalidatemerchant = function (event) {
                var form = $(this.formSelector);
                var formData = new FormData(form[0]);
                formData.append('validationURL', event.validationURL);

                $.ajax({
                    global: false,
                    context: this,
                    type: 'POST',
                    url: url.build('mollie/applePay/buyNowValidation'),
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (result) {
                        this.cartId = result.cartId;

                        this.session.completeMerchantValidation(result.validationData);
                    },
                    error: this.handleAjaxError
                })
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
                        cartId: this.cartId,
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
                        cartId: this.cartId,
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
                    url: url.build('mollie/applePay/buyNowPlaceOrder'),
                    data: {
                        cartId: this.cartId,
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

                        this.session.completePayment(ApplePaySession.STATUS_SUCCESS);

                        setTimeout( function () {
                            location.href = result.url
                        }, 1000);
                    }.bind(this),
                    error: this.handleAjaxError
                })

                try {
                    this.placeOrder(this);
                } catch {
                    this.session.completePayment(ApplePaySession.STATUS_ERROR);
                }
            }.bind(this);

            this.session.oncancel = function (event) {
                this.session = null;
                this.shippingMethods = null;
                this.selectedShippingMethod = null;
            }.bind(this);

            this.session.begin();
        },

        canUseApplePay: function () {
            try {
                return window.ApplePaySession && window.ApplePaySession.canMakePayments();
            } catch (error) {
                console.warn('Error when trying to check Apple Pay:', error);
                return false;
            }
        },

        getLineItems: function () {
            let totals = [...this.quoteTotals];

            // Delete the item that has code == grand_total
            totals.splice(totals.findIndex(total => total.code === 'grand_total'), 1);

            return totals;
        },

        getTotal: function () {
            let totals = [...this.quoteTotals];

            const total = totals.find(total => total.code === 'grand_total');

            total.label = this.storeName;

            return total;
        },

        handleAjaxError: function (response) {
            this.session.abort();

            var customerMessages = customerData.get('messages')() || {},
                messages = customerMessages.messages || [];

            messages.push({
                text: response.responseJSON.message,
                type: 'error'
            });

            customerMessages.messages = messages;

            customerData.set('messages', customerMessages);
        }
    });
})
