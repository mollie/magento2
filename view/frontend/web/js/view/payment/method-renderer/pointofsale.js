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
                    template: 'Mollie_Payment/payment/pointofsale',
                    selectedTerminal: ko.observable()
                },

                initialize: function () {
                    this._super();

                    if (!window.localStorage) {
                        return;
                    }

                    var key = this.getCode() + '_terminal';
                    this.selectedTerminal.subscribe( function (value) {
                        window.localStorage.setItem(key, value);
                    }.bind(this));

                    this.selectDefaultTerminal(key);
                },

                selectDefaultTerminal: function (key) {
                    if (window.localStorage.getItem(key)) {
                        this.selectedTerminal(window.localStorage.getItem(key));
                        return;
                    }

                    var terminalList = this.getTerminals();
                    if (terminalList.length === 1) {
                        this.selectedTerminal(terminalList[0].id);
                    }
                },

                getTerminals: function () {
                    return checkoutConfig && checkoutConfig.mollie.terminals ? checkoutConfig.mollie.terminals : [];
                },

                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            "selected_terminal": this.selectedTerminal()
                        }
                    };
                },

                validate: function () {
                    return this.selectedTerminal();
                }
            }
        );
    }
);
