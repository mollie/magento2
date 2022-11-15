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
                    selectedIssuer: ko.observable()
                },
                initialize: function () {
                    this._super();

                    if (!window.sessionStorage) {
                        return;
                    }

                    var key = this.getCode() + '_issuer';
                    if (window.sessionStorage.getItem(key)) {
                        this.selectedIssuer(window.sessionStorage.getItem(key));
                    }

                    this.selectedIssuer.subscribe( function (value) {
                        window.sessionStorage.setItem(key, value);
                    }.bind(this));
                },
                getForm: function () {
                    return $('#' + this.item.method + '-form');
                },
                getIssuers: function () {
                    return checkoutConfig && checkoutConfig.issuers ? checkoutConfig.issuers[this.item.method] : [];
                },
                getIssuerListType: function () {
                    return checkoutConfig.issuersListType ? checkoutConfig.issuersListType[this.item.method] : 'dropdown';
                },
                getSelectedIssuer: function () {
                    if (this.getIssuerListType() !== 'radio' &&
                        this.getIssuerListType() !== 'dropdown') {
                        return;
                    }

                    return this.selectedIssuer();
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
