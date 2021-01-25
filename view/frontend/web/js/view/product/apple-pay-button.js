/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'mage/url',
    'mage/translate',
    'jquery'
], function (
    Component,
    url,
    __,
    $
) {
    return Component.extend({
        cartId: null,
        shippingMethods: [],
        selectedShippingMethod: null,
        paymentFeeAmount: null,
        session: null,

        // Variables that are set from the template: view/frontend/templates/product/view/applepay.phtml
        formSelector: null,
        currencyCode: null,
        countryCode: null,
        productName: null,
        storeName: null,

        initObservable: function () {
            this._super().observe([
                'applePayPaymentToken'
            ]);

            return this;
        },

        payWithApplePay: function () {
            // Steps:
            // 1. Create new quote. - Done
            // 2. Add current product to the quote. - Done
            // 3. Set shipping method on quote.
            // 4. Set payment method on quote (apple pay).
            // 5. Initialize Apple pay.
            // 6. When verified, place order.
            // 7. Redirect user to success page.

            this.createApplePaySession();
        },

        getProductPrice: function () {
            return $('[data-role=priceBox][data-product-id] [itemprop="price"]').attr('content');
        },

        createApplePaySession: function () {
            var request = {
                countryCode: this.countryCode,
                currencyCode: this.currencyCode,
                supportedNetworks: ['amex', 'maestro', 'masterCard', 'visa', 'vPay'],
                merchantCapabilities: ['supports3DS'],
                total: {
                    label: this.productName,
                    amount: this.getTotal().amount,
                },
                shippingType: 'shipping',
                requiredBillingContactFields: [
                    'postalAddress',
                    'name',
                    'email'
                ],
                requiredShippingContactFields: [
                    'postalAddress',
                    'name',
                    'email'
                ]
            }

            if (!this.session) {
                this.session = new ApplePaySession(10, request);
            }

            this.session.onshippingcontactselected = function (event) {
                $.ajax({
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
                        this.paymentFeeAmount = result.mollie_payment_fee;

                        this.session.completeShippingContactSelection(
                            ApplePaySession.STATUS_SUCCESS,
                            result.shipping_methods,
                            this.getTotal(),
                            this.getLineItems()
                        )
                    }.bind(this)
                })
            }.bind(this)

            this.session.onshippingmethodselected = function (event) {
                this.selectedShippingMethod = event.shippingMethod

                this.session.completeShippingMethodSelection(
                    ApplePaySession.STATUS_SUCCESS,
                    this.getTotal(),
                    this.getLineItems()
                )
            }.bind(this);

            this.session.onpaymentauthorized = function (event) {
                console.log('onpaymentauthorized', event);

                $.ajax({
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
                        console.log('order placed', result);
                    }.bind(this)
                })

                try {
                    // this.placeOrder(this);
                } catch {
                    this.session.completePayment(ApplePaySession.STATUS_ERROR);
                }
            }.bind(this);

            this.session.onvalidatemerchant = function (event) {
                console.log('onvalidatemerchant');

                var form = $(this.formSelector);
                var formData = new FormData(form[0]);
                formData.append('validationURL', event.validationURL);

                $.ajax({
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
                    }.bind(this)
                })
            }.bind(this);

            this.session.oncancel = function () {
                console.log('oncancel');
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
            var lines = [];

            lines.push({
                type: 'final',
                label: this.productName,
                amount: this.getProductPrice(),
            })

            if (this.selectedShippingMethod) {
                lines.push({
                    type: 'final',
                    label: this.selectedShippingMethod.label,
                    amount: this.selectedShippingMethod.amount,
                })
            }

            if (this.paymentFeeAmount) {
                lines.push({
                    type: 'final',
                    label: __('Apple Pay fee'),
                    amount: this.paymentFeeAmount,
                })
            }

            return lines;
        },

        getTotal: function () {
            var total = this.getLineItems()
                .map(item => parseFloat(item.amount))
                .reduce((a, b) => a + b, 0);

            return {
                type: 'final',
                label: this.storeName,
                amount: total
            };
        }
    });
})
