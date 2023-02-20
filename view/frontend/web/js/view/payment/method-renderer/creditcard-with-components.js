define([
    'jquery',
    'Mollie_Payment/js/view/payment/method-renderer/creditcard',
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
                this.mollie = Mollie(checkoutConfig.mollie.profile_id, this.getMollieOptions());
            } catch (error) {
                console.error(error);
                return this;
            }

            return this;
        },

        getMollieOptions: function () {
            var options = {
                testMode: checkoutConfig.mollie.testmode
            };

            if (checkoutConfig.mollie.locale) {
                options['locale'] = checkoutConfig.mollie.locale;
            }

            return options;
        },

        checkIfVisible: function () {
            if (!this.rendered() && !this.mounted()) {
                return;
            }

            if (this.isChecked() === this.getCode()) {
                setTimeout(function () {
                    this.mount();
                }.bind(this));
            }
        },

        getData: function () {
            var data = {
                'method': this.item.method,
                'po_number': null,
                'additional_data': {
                    'card_token': this.cardToken
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
            this.getVaultEnabler().visitAdditionalData(data);

            return data;
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

            let cardHolder = this.mollie.createComponent('cardHolder', this.getOptions('cardHolder'));
            let cardNumber = this.mollie.createComponent('cardNumber', this.getOptions('cardNumber'));
            let expiryDate = this.mollie.createComponent('expiryDate', this.getOptions('expiryDate'));
            let verificationCode = this.mollie.createComponent('verificationCode', this.getOptions('verificationCode'));

            this.mountElement(cardHolder, '#card-holder');
            this.mountElement(cardNumber, '#card-number');
            this.mountElement(expiryDate, '#expiry-date');
            this.mountElement(verificationCode, '#verification-code');

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

        /**
         * Overwrite this file or make a mixin and let this function return your desired style options.
         *
         * @param type
         * @returns {{}}
         */
        getOptions: function (type) {
            return {};
        },
    });
});
