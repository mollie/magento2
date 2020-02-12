define([
    'jquery',
    'Mollie_Payment/js/view/payment/method-renderer/default',
    'https://js.mollie.com/v1/mollie.js'
],
function ($, Component, Mollie) {
    'use strict';
    var checkoutConfig = window.checkoutConfig.payment;

    return Component.extend({
        components: {},
        cardToken: null,
        mollie: null,
        redirectAfterPlaceOrder: false,
        defaults: {
            template: 'Mollie_Payment/payment/creditcard-with-components',
            rendered: false,
            mounted: false
        },

        initObservable: function () {
            this._super().observe(['rendered', 'mounted']);

            return this;
        },

        initialize: function () {
            this._super();

            this.isChecked.subscribe(this.checkIfVisible, this);
            this.rendered.subscribe(this.checkIfVisible, this);
            this.checkIfVisible();

            try {
                this.mollie = Mollie(checkoutConfig.mollie.profile_id, {
                    locale: checkoutConfig.mollie.locale,
                    testMode: checkoutConfig.mollie.testmode
                });

                this.components.cardHolder = this.mollie.createComponent('cardHolder', this.getOptions('cardHolder'));
                this.components.cardNumber = this.mollie.createComponent('cardNumber', this.getOptions('cardNumber'));
                this.components.expiryDate = this.mollie.createComponent('expiryDate', this.getOptions('expiryDate'));
                this.components.verificationCode = this.mollie.createComponent('verificationCode', this.getOptions('verificationCode'));
            } catch (error) {
                console.error(error);
                return this;
            }

            return this;
        },

        checkIfVisible: function () {
            if (!this.rendered()) {
                return;
            }

            if (this.isChecked() === this.getCode()) {
                this.mount();
            } else {
                this.unmount();
            }
        },

        getData: function () {
            return {
                'method': this.item.method,
                'po_number': null,
                'additional_data': {
                    'card_token': this.cardToken
                }
            };
        },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            this.isPlaceOrderActionAllowed(false);
            var parent = this._super.bind(this);
            this.mollie.createToken().then( function (result) {
                if (result.error) {
                    this.messageContainer.addErrorMessage({message: result.error.message});
                    this.isPlaceOrderActionAllowed(true);
                }

                if (result.token) {
                    this.cardToken = result.token;
                    parent(data, event);
                }
            }.bind(this));
        },

        mount: function () {
            if (this.mounted()) {
                return;
            }

            this.mountElement(this.components.cardHolder, '#card-holder');
            this.mountElement(this.components.cardNumber, '#card-number');
            this.mountElement(this.components.expiryDate, '#expiry-date');
            this.mountElement(this.components.verificationCode, '#verification-code');

            this.mounted(true);
        },

        mountElement: function (element, id) {
            element.mount(id);

            var errorElement = document.querySelector(id + '-error');
            element.addEventListener('change', function (event) {
                if (event.error && event.touched) {
                    errorElement.textContent = event.error;
                    errorElement.style.display = 'block';
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
            });
        },

        unmount: function () {
            if (!this.mounted()) {
                return;
            }

            this.components.cardHolder.unmount('#card-holder');
            this.components.cardNumber.unmount('#card-number');
            this.components.expiryDate.unmount('#expiry-date');
            this.components.verificationCode.unmount('#verification-code');

            this.mounted(false);
        },

        /**
         * Overwrite this file or make a mixin and let this function return your desired style options.
         *
         * @param type
         * @returns {{}}
         */
        getOptions: function (type) {
            return {};
        }
    });
});
