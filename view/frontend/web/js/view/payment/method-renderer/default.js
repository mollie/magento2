define(
    [
        'jquery',
        'underscore',
        'ko',
        'mage/url',
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Mollie_Payment/js/model/checkout-config',
        'jquery/jquery-storageapi'
    ],
    function (
        $,
        _,
        ko,
        url,
        storage,
        $t,
        Component,
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
                initialize: function () {
                    this._super();

                    this.isChecked.subscribe( function () {
                        if (this.getCode() !== this.isChecked()) {
                            return;
                        }

                        this.renderMessages();
                    }.bind(this));

                    if (this.getCode() === this.isChecked()) {
                        this.renderMessages();
                    }

                    return this;
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
                    this.beforePlaceOrder().done(function (response) {
                        this.isPlaceOrderActionAllowed(true);

                        if (!this.paymentToken()) {
                            this.onPaymentTokenFailed(response);
                            return;
                        }

                        parent(data, event);
                    }.bind(this)).fail(function (response) {
                        this.isPlaceOrderActionAllowed(true);
                        this.onPaymentTokenFailed(response);
                    }.bind(this));
                },
                onPaymentTokenFailed: function (response) {
                    console.error('Mollie: unable to retrieve the payment token, the order has not been placed.', response);

                    this.messageContainer.addErrorMessage({
                        message: $t('We were unable to start your payment. Please try again.')
                    });
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
                        this.paymentToken(result);
                    }.bind(this));

                    return promise;
                },
                afterPlaceOrder: function () {
                    this._super();

                    window.location = this.getRedirectUrl();
                },
                /**
                 * Without a token the redirect controller falls back to the last order of the checkout session,
                 * so an order is never left behind unpaid when the payment token could not be retrieved.
                 */
                getRedirectUrl: function () {
                    if (!this.paymentToken()) {
                        return url.build('mollie/checkout/redirect');
                    }

                    return url.build('mollie/checkout/redirect/paymentToken/' + this.paymentToken());
                },
                renderMessages: function () {
                    // Copied from Magento_Theme/js/view/messages
                    var messages = _.unique($.cookieStorage.get('mage-messages'), 'text');

                    $.each(messages, function (index, row) {
                        if (row.type === 'success') {
                            this.messageContainer.addSuccessMessage({message: row.text});
                        } else {
                            this.messageContainer.addErrorMessage({message: row.text});
                        }
                    }.bind(this));

                    // Copied from Magento_Theme/js/view/messages
                    $.mage.cookies.set('mage-messages', '', {
                        samesite: 'strict',
                        domain: ''
                    });

                    if (!messages.length) {
                        return;
                    }

                    // Make sure the messages are visible
                    var attempts = 0;
                    var interval = setInterval(function () {
                        attempts++;

                        if (attempts > 10) {
                            clearInterval(interval);
                            return;
                        }

                        var element = $('.payment-method._active [data-role="checkout-messages"]');
                        if (!element.length) {
                            return;
                        }

                        clearInterval(interval);
                        if (!this.isInViewport(element.get(0))) {
                            $([document.documentElement, document.body]).animate({
                                scrollTop: element.offset().top - 100
                            }, 500);
                        }
                    }.bind(this), 100);
                },
                isInViewport: function (element) {
                    var bounding = element.getBoundingClientRect();

                    return (
                        bounding.top >= 0 &&
                        bounding.left >= 0 &&
                        bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                        bounding.right <= (window.innerWidth || document.documentElement.clientWidth)
                    );
                },
                getClassNames: function () {
                    return ko.computed( function () {
                        var output = 'payment-method-' + this.getCode();
                        if (this.getCode() === this.isChecked()) {
                            output += ' _active';
                        }

                        return output;
                    }.bind(this));
                }
            }
        );
    }
);
