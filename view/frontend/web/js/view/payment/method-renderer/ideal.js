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
                    template: 'Mollie_Payment/payment/ideal',
                    selectedIssuer: null
                },
                getIssuers: function () {
                    return checkoutConfig.issuers[this.item.method];
                },
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            "selected_issuer": this.selectedIssuer
                        }
                    };
                }
            }
        );
    }
);