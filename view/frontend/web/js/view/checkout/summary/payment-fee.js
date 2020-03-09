define(
    [
        'knockout',
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (
        ko,
        Component,
        quote,
        priceUtils,
        totals
    ) {
        return Component.extend({
            defaults: {
                isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
                template: 'Mollie_Payment/checkout/summary/payment-fee'
            },

            totals: quote.getTotals(),
            isTaxDisplayedInGrandTotal: window.checkoutConfig.includeTaxInGrandTotal || false,

            initialize: function() {
                this._super();

                this.price = ko.computed( function () {
                    var price = 0,
                        segment = totals.getSegment('mollie_payment_fee');

                    if (this.totals() && segment) {
                        price = segment.value;
                    }

                    return price;
                }, this);
            },

            isDisplayed: function() {
                return this.price();
            },

            getValue: function() {
                return this.getFormattedPrice(this.price());
            }
        });
    }
);
