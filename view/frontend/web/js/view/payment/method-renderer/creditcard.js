define([
    'jquery',
    'Mollie_Payment/js/view/payment/method-renderer/default',
],
function ($, Component) {
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

            return data;
        },
    });
});
