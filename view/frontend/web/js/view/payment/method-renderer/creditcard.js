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

        getData: function () {
            var data = {
                'method': this.item.method,
            };

            data['additional_data'] = _.extend({}, this.additionalData);
            this.getVaultEnabler().visitAdditionalData(data);

            return data;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            if (!window.checkoutConfig.payment.mollie.vault.enabled) {
                return false;
            }

            return this.getVaultEnabler().isVaultEnabled();
        },

        getVaultEnabler: function () {
            if (this.vaultEnabler) {
                return this.vaultEnabler;
            }

            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode('mollie_methods_creditcard_vault');

            return this.vaultEnabler;
        }
    });
});
