/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'Mollie_Payment/js/model/payment-token',
    'mage/translate'
], function (VaultComponent, paymentToken, $t) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Mollie_Payment/payment/vault',
            redirectAfterPlaceOrder: false,
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * Get public hash
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },

        /**
         * Get public hash
         * @returns {String}
         */
        getName: function () {
            return this.details.name;
        },

        /**
         * Get public hash
         * @returns {String}
         */
        getIcon: function () {
            return require.toUrl('Mollie_Payment/images/creditcard-issuers/' + this.details.type + '.svg');
        },

        placeOrder: function (data, event) {
            paymentToken.placeOrder(this, this._super.bind(this), data, event);
        },

        /**
         * @override
         */
        getData: function () {
            var data = this._super();

            data['additional_data']['is_active_payment_token_enabler'] = true;

            return data;
        },

        afterPlaceOrder: function () {
            this._super();
            paymentToken.afterPlaceOrder();
        }
    });
});
