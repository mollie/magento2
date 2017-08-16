define(
    [
        'ko',
        'jquery',
        'Mollie_Payment/js/view/payment/method-renderer/default'
    ],
    function (ko, $, Component) {
        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend(
            {
                defaults: {
                    template: 'Mollie_Payment/payment/giftcard',
                    selectedIssuer: null
                },
                getForm: function () {
                    return $('#' + this.item.method + '-form');
                },
                getIssuers: function () {
                    return checkoutConfig.issuers[this.item.method];
                },
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            "selected_issuer": $('input[name=issuer]:checked', this.getForm()).val()
                        }
                    };
                },
                validate: function () {
                    var $form = this.getForm();
                    return $form.validation() && $form.validation('isValid');
                }
            }
        );
    }
);