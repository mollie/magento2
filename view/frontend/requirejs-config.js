var config = {
    config: {
        mixins: {
            'Magento_InstantPurchase/js/view/instant-purchase': {
                'Mollie_Payment/js/view/instant-purchase/instant-purchase': true
            },
            'Onestepcheckout_Iosc/js/ajax': {
                'Mollie_Payment/js/mixin/onestepcheckout/ajax-mixin': true
            }
        }
    }
};
