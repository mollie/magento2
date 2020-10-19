require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'prototype',
    'loader'
], function ($, modal) {

    /**
     * @param{String} modalSelector - modal css selector.
     * @param{Object} options - modal options.
     */
    function initModal(modalSelector, options) {
        var $resultModal = $(modalSelector);

        if (!$resultModal.length) return;

        var popup = modal(options, $resultModal);
        $resultModal.loader({texts: ''});
    }

    var successHandlers = {
        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        debug: function (result, $container) {

            if (Array.isArray(result)) {

                var lisHtml = result.map(function (err) {
                    return '<li class="mm-mollie-result_debug-item"><strong>' + err.date + '</strong><p>' + err.msg + '</p></li>';
                }).join('');

                $container.find('.result').empty().append('<ul>' + lisHtml + '</ul>');
            } else {

                $container.find('.result').empty().append(result);
            }
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        error: function (result, $container) {

            if (Array.isArray(result)) {

                var lisHtml = result.map(function (err) {
                    return '<li class="mm-mollie-result_error-item"><strong>' + err.date + '</strong><p>' + err.msg + '</p></li>';
                }).join('');

                $container.find('.result').empty().append('<ul>' + lisHtml + '</ul>');
            } else {

                $container.find('.result').empty().append(result);
            }
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        test: function (result, $container) {
            var html = '<li class="mm-mollie-result_test-item ' + (result.success ? 'success' : 'failed')
                + '">'
                + '<div><p><em>' + result.msg + '</em></p></div></li>';

            $container.find('.result').html(html);
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        version: function (result, $container) {

            var resultHtml = '';
            var currentVersion = result.current_verion.replace(/v|version/gi, '');
            var latestVersion = result.last_version.replace(/v|version/gi, '');

            if (currentVersion === latestVersion) {
                resultHtml = '<strong class="mm-mollie-version mm-mollie-icon__thumbs-up">'
                    + $.mage.__('Great, you are using the latest version.')
                    + '</strong>';
            } else {

                var translatedResult = $.mage.__('There is a new version available <span>(%1)</span> see <button type="button" id="mm-mollie-button_changelog">changelog</button>.')
                    .replace('%1', latestVersion);

                resultHtml = '<strong class="mm-mollie-version mm-mollie-icon__thumbs-down">'
                    + translatedResult
                    + '</strong>';
            }

            $container.html(resultHtml);
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        changelog: function (result, $container) {

            var lisHtml = Object.keys(result).map(function (key) {

                var version = key;
                var date = result[key].date;
                var resultHtml = result[key].changelog;

                return '<li class="mm-mollie-result_changelog-item"><b>'
                    + version + '</b><span class="mm-mollie-divider">|</span><b>'
                    + date + '</b><div>'
                    + resultHtml + '</div></li>';
            }).join('');

            $container.find('.result').empty().append(lisHtml);
        },
    }

    // init debug modal
    initModal('#mm-mollie-result_debug-modal', {
        type: 'popup',
        responsive: true,
        innerScroll: true,
        title: $.mage.__('Last 100 debug log lines'),
        buttons: [
            {
                text: $.mage.__('Download as .txt file'),
                class: 'mm-mollie-button__download mm-mollie-icon__download-alt',
                click: function () {

                    var elText = document.getElementById('mm-mollie-result_debug').innerText || '';
                    var link = document.createElement('a');

                    link.setAttribute('download', 'debug-log.txt');
                    link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                    link.click();
                },
            },
            {
                text: $.mage.__('ok'),
                class: '',
                click: function () {
                    this.closeModal();
                },
            }
        ]
    });

    // init error modal
    initModal('#mm-mollie-result_error-modal', {
        type: 'popup',
        responsive: true,
        innerScroll: true,
        title: $.mage.__('Last 100 error log records'),
        buttons: [
            {
                text: $.mage.__('Download as .txt file'),
                class: 'mm-mollie-button__download mm-mollie-icon__download-alt',
                click: function () {

                    var elText = document.getElementById('mm-mollie-result_error').innerText || '';
                    var link = document.createElement('a');

                    link.setAttribute('download', 'error-log.txt');
                    link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                    link.click();
                },
            },
            {
                text: $.mage.__('ok'),
                class: '',
                click: function () {
                    this.closeModal();
                },
            }
        ]
    });

    // init selftest modal
    initModal('#mm-mollie-result_test-modal', {
        type: 'popup',
        responsive: true,
        innerScroll: true,
        title: $.mage.__('Self-test'),
        buttons: [
            {
                text: $.mage.__('ok'),
                class: '',
                click: function () {
                    this.closeModal();
                },
            }
        ]
    });

    // init changelog modal
    initModal('#mm-mollie-result_changelog-modal', {
        type: 'popup',
        responsive: true,
        innerScroll: true,
        title: 'Changelog',
        buttons: [
            {
                text: $.mage.__('ok'),
                class: '',
                click: function () {
                    this.closeModal();
                },
            }
        ]
    });

    // init loader on the Check Version block
    $('.mm-mollie-result_version-wrapper').loader({texts: ''});

    /**
     * Ajax request event
     */
    $(document).on('click', '[id^=mm-mollie-button]', function () {
        var actionName = this.id.split('_')[1];
        var $modal = $('#mm-mollie-result_' + actionName + '-modal');
        var $result = $('#mm-mollie-result_' + actionName);

        if (actionName === 'version') {
            $(this).fadeOut(300).addClass('mm-mollie-disabled');
            $modal = $('.mm-mollie-result_' + actionName + '-wrapper');
            $modal.loader('show');
        } else {
            $modal.modal('openModal').loader('show');
        }

        $result.hide();

        new Ajax.Request($modal.data('mm-mollie-endpoint-url'), {
            loaderArea: false,
            asynchronous: true,
            onSuccess: function (response) {

                if (response.status > 200) {
                    var result = response.statusText;
                } else {
                    successHandlers[actionName](response.responseJSON.result || response.responseJSON, $result);

                    $result.fadeIn();
                    $modal.loader('hide');
                    $modal.loader('hide');
                }
            }
        });
    });
});
