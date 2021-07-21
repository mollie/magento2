define([
    'jquery',
    'mage/translate',
], function ($, $t) {
    return function (config, wrapper) {
        var button = $('.fetch-mollie-payment-status', wrapper);
        var row = $('.mollie-order-status-result', wrapper);

        button.click(function () {
            button.text($t('Fetching...'));
            button.prop('disabled', true);
            row.hide();

            $.ajax({
                url: config.endpoint,
                method: 'POST',
                data: {order_id: config.order_id},
                success: function (result) {
                    if (result.error) {
                        row.html('<th>' + $t('Error While Fetching') + '</th><td class="mollie-error">' + result.msg + '</td>');
                        row.show();
                    } else {
                        location.reload();
                    }
                },
                error: function (result) {
                    row.html('<th>' + $t('Error While Fetching') + '</th><td class="mollie-error">' + result.responseJSON.msg + '</td>');
                    row.show();
                },
                complete: function () {
                    button.prop('disabled', false);
                    button.text($t('Fetch Status'));
                }
            })
        });
    }
});
