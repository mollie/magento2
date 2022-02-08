define([
    'jquery'
], function ($) {
    return function (component) {

        return component.extend({
            instantPurchase: function () {
                function checkInstantPurchaseResponse (result, xhr) {
                    // When the response contains an URL we need to redirect
                    if (xhr.responseJSON.mollie_redirect_url) {
                        window.location = xhr.responseJSON.mollie_redirect_url;
                    }

                    $(document).off('ajaxComplete', checkInstantPurchaseResponse);
                }

                $(document).on('ajaxComplete', checkInstantPurchaseResponse);

                return this._super();
            }
        });
    }
})
