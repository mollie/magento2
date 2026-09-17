define(
    [
        'jquery',
        'ko',
        'mage/url',
        'mage/storage',
        'mage/translate',
        'uiLayout',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Mollie_Payment/js/model/checkout-config',
        'Mollie_Payment/js/model/messages',
        'jquery/jquery-storageapi'
    ],
    function (
        $,
        ko,
        url,
        storage,
        $t,
        layout,
        Component,
        quote,
        checkoutData,
        customer,
        urlBuilder,
        checkoutConfigData,
        Messages
    ) {
        'use strict';

        var checkoutConfig = window.checkoutConfig.payment;
        var messageContainerMethods = {
            success: 'addSuccessMessage',
            notice: 'addNoticeMessage'
        };

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
                /**
                 * Replaces the message container and its renderer with the Mollie variants, which can render a
                 * notice instead of collapsing it into an error.
                 */
                initChildren: function () {
                    this.messageContainer = new Messages();
                    this.createMessagesComponent();

                    return this;
                },
                createMessagesComponent: function () {
                    layout([{
                        parent: this.name,
                        name: this.name + '.messages',
                        displayArea: 'messages',
                        component: 'Magento_Ui/js/view/messages',
                        config: {
                            messageContainer: this.messageContainer,
                            template: 'Mollie_Payment/messages'
                        }
                    }]);

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
                    var messages = this.takeMollieMessages();

                    if (!messages.length) {
                        return;
                    }

                    this.removeFromPageMessages(messages);

                    $.each(messages, function (index, row) {
                        var method = messageContainerMethods[row.type] || 'addErrorMessage';

                        this.messageContainer[method]({message: row.text});
                    }.bind(this));

                    this.scrollToMessages();
                },
                /**
                 * The messages Mollie added right before redirecting back to the checkout. They are consumed once,
                 * so selecting a second payment method does not render them again.
                 */
                takeMollieMessages: function () {
                    var messages = (checkoutConfig.mollie && checkoutConfig.mollie.messages) || [];

                    if (checkoutConfig.mollie) {
                        checkoutConfig.mollie.messages = [];
                    }

                    return messages;
                },
                /**
                 * The `mage-messages` cookie is shared with the rest of the shop and the checkout page has no
                 * renderer to empty it, so only the messages rendered here are removed. Anything else stays behind
                 * for the page it belongs to.
                 */
                removeFromPageMessages: function (messages) {
                    var pageMessages = $.cookieStorage.get('mage-messages');

                    if (!Array.isArray(pageMessages)) {
                        return;
                    }

                    var rendered = messages.map(function (row) {
                        return row.text;
                    });

                    var remaining = pageMessages.filter(function (row) {
                        return rendered.indexOf(row.text) === -1;
                    });

                    // Copied from Magento_Theme/js/view/messages
                    $.mage.cookies.set('mage-messages', remaining.length ? JSON.stringify(remaining) : '', {
                        samesite: 'strict',
                        domain: ''
                    });
                },
                scrollToMessages: function () {
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
