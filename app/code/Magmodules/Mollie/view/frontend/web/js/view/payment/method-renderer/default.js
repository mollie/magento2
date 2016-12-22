define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
    ],
    function (
        ko,
        $,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        url
    ) {

        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend({

            defaults: {
                template: 'Magmodules_Mollie/payment/default',
                selectedIssuer: null,
            },
            
            selectedIssuer: ko.observable(),
            
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            showIssuers: function () {
                if (this.item.method == 'mollie_methods_ideal') {
                    return true;
                } else {
                    return false;
                }
            },
            
            getIssuers: function () {
                return checkoutConfig.issuers;
            },

            getMethodImage: function () {
                return checkoutConfig.image[this.item.method];
            },
            afterPlaceOrder: function () {
                if (this.item.method == 'mollie_methods_ideal') {
                    window.location.replace(url.build('mollie/checkout/redirect/?issuer='+$('[name="issuer"]').val()));
                } else {
                    window.location.replace(url.build('mollie/checkout/redirect/'));
                }
            },
            getInstructions: function () {
                return checkoutConfig.instructions[this.item.method];
            },
        });
    }
);
