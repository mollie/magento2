define(
    [
        'jquery',
        'mage/url',
        'mage/storage',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Mollie_Payment/js/model/checkout-config'
    ],
    function (
        $,
        url,
        storage,
        Component,
        placeOrderAction,
        quote,
        checkoutData,
        customer,
        urlBuilder,
        checkoutConfigData
    ) {
        'use strict';

        var checkoutConfig = window.checkoutConfig.payment;

        return Component.extend(
            {
                redirectAfterPlaceOrder: false,
                defaults: {
                    template: 'Mollie_Payment/payment/default'
                },
                initObservable: function () {
                    this._super().observe([
                        'paymentToken'
                    ]);

                    var config = checkoutConfigData() ? checkoutConfigData().selectedMethod : null;
                    var shouldSelect = this.item.method === config || config === 'first_mollie_method';
                    if (!checkoutData.getSelectedPaymentMethod() && shouldSelect) {
                        this.selectPaymentMethod();
                    }

                    return this;
                },
                getMethodImage: function () {
                    return checkoutConfig.image[this.item.method];
                },
                getInstructions: function () {
                    return checkoutConfig.instructions[this.item.method];
                },
                placeOrder: function (data, event) {
                    this.isPlaceOrderActionAllowed(false);
                    var parent = this._super.bind(this);
                    this.beforePlaceOrder().always(function () {
                        this.isPlaceOrderActionAllowed(true);
                        parent(data, event);
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

                    promise.success( function (result) {
                        this.paymentToken(result);
                    }.bind(this));

                    return promise;
                },
                afterPlaceOrder: function () {
                    window.location = url.build('mollie/checkout/redirect/paymentToken/' + this.paymentToken());
                }
            }
        );
    }
);
