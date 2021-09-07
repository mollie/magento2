define([
    'jquery',
    'Mollie_Payment/js/view/payment/method-renderer/default',
    'Magento_Vault/js/view/payment/vault-enabler'
],
function ($, Component, VaultEnabler) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Mollie_Payment/payment/creditcard',
        },

        initialize: function () {
            this._super();

            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode('mollie_methods_creditcard_vault');

            return this;
        },

        getData: function () {
            var data = {
                'method': this.item.method,
            };

            data['additional_data'] = _.extend({}, this.additionalData);
            this.vaultEnabler.visitAdditionalData(data);

            return data;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            if (!window.checkoutConfig.payment.mollie.vault.enabled) {
                return false;
            }

            return this.vaultEnabler.isVaultEnabled();
        },
    });
});
