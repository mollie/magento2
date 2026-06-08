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
            this._super().observe(['rendered', 'mounted', 'saveCard', 'selectedMandateId', 'consentTimestamp']);

            this.saveCard(false);
            this.selectedMandateId(null);
            this.consentTimestamp(null);

            this.saveCard.subscribe(function (value) {
                this.consentTimestamp(value ? new Date().toISOString() : null);
            }.bind(this));

            // When switching back to a new card, KO re-renders the card form into the DOM.
            // Reset mounted so mount() runs again, but only after the template has rendered.
            this.selectedMandateId.subscribe(function (mandateId) {
                if (!mandateId && this.rendered()) {
                    this.mounted(false);
                    setTimeout(function () {
                        this.mount();
                    }.bind(this));
                }
            }.bind(this));

            return this;
        },

        initialize: function () {
            this._super();

            this.isChecked.subscribe(this.checkIfVisible, this);
            this.rendered.subscribe(this.checkIfVisible, this);
            this.checkIfVisible();

            // Pre-select first mandate if available
            var mandates = this.getSavedMandates();
            if (mandates.length > 0) {
                this.selectedMandateId(mandates[0].mandate_id);
            }

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

            if (this.isChecked() === this.getCode() && this.isUsingNewCard()) {
                setTimeout(function () {
                    this.mount();
                }.bind(this));
            }
        },

        getSavedMandates: function () {
            var cc = checkoutConfig.mollie && checkoutConfig.mollie.creditcard;
            return (cc && cc.saved_cards_enabled && cc.saved_mandates) ? cc.saved_mandates : [];
        },

        isSavedCardsEnabled: function () {
            var cc = checkoutConfig.mollie && checkoutConfig.mollie.creditcard;
            return !!(cc && cc.saved_cards_enabled);
        },

        getConsentText: function () {
            var cc = checkoutConfig.mollie && checkoutConfig.mollie.creditcard;
            return (cc && cc.consent_text) ? cc.consent_text : '';
        },

        cardLabelToSlug: function (label) {
            var map = {
                'Visa': 'visa',
                'Mastercard': 'mastercard',
                'American Express': 'amex',
                'Maestro': 'maestro',
                'Carte Bancaire': 'cartebancaire',
                'V PAY': 'vpay'
            };
            return map[label] || null;
        },

        getCardLogoUrl: function (mandate) {
            var cc = checkoutConfig.mollie && checkoutConfig.mollie.creditcard;
            var baseUrl = cc && cc.card_logo_base_url;
            if (!baseUrl) {
                return '';
            }
            var slug = this.cardLabelToSlug(mandate.card_label);
            var base = baseUrl.slice(-1) === '/' ? baseUrl : baseUrl + '/';
            return slug ? base + slug + '.svg' : '';
        },

        isUsingNewCard: function () {
            return typeof this.selectedMandateId !== 'function' || !this.selectedMandateId();
        },

        getData: function () {
            // Guard: observables may not be initialized yet if getData() is called during parent initObservable
            var mandateId = typeof this.selectedMandateId === 'function' ? this.selectedMandateId() : null;
            var saveCard = typeof this.saveCard === 'function' ? this.saveCard() : false;
            var timestamp = typeof this.consentTimestamp === 'function' ? this.consentTimestamp() : null;
            var usingNewCard = !mandateId;

            var data = {
                'method': this.item.method,
                'po_number': null,
                'additional_data': {
                    'card_token': this.cardToken,
                    'mollie_save_card': (usingNewCard && saveCard) ? true : null,
                    'mollie_mandate_id': mandateId || null,
                    'mollie_consent_timestamp': (usingNewCard && saveCard) ? timestamp : null
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            return data;
        },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            this.isPlaceOrderActionAllowed(false);
            var parent = this._super.bind(this);

            // When using a saved mandate, skip card tokenization.
            if (this.selectedMandateId()) {
                parent(data, event);
                return;
            }

            this.mollie.createToken().then(function (result) {
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
