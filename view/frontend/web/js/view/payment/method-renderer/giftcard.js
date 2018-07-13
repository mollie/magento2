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
                getIssuerListType: function () {
                    return checkoutConfig.issuersListType[this.item.method];
                },
                getSelectedIssuer: function () {
                    if (this.getIssuerListType() === 'radio') {
                        return $('input[name=issuer]:checked', this.getForm()).val();
                    }
                    if (this.getIssuerListType() === 'dropdown') {
                        return this.selectedIssuer;
                    }
                },
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            "selected_issuer": this.getSelectedIssuer()
                        }
                    };
                },
                validate: function () {
                    var $form = this.getForm();
                    if (this.getIssuerListType() === 'radio') {
                        return $form.validation() && $form.validation('isValid');
                    }
                    return $form.validation();
                }
            }
        );
    }
);