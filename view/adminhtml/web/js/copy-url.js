/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function (
    $
) {
    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;

        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';

        document.body.removeChild(textArea);
    }

    function copyTextToClipboard(text) {
        if (!navigator.clipboard) {
            fallbackCopyTextToClipboard(text);
            return;
        }

        navigator.clipboard.writeText(text).then(function() {
            console.log('Async: Copying to clipboard was successful!');
        }, function(err) {
            throw err;
        });
    }

    return function (config, elements) {
        $(elements).click( function () {
            try {
                copyTextToClipboard($(this).data('url'));
                $(this).css('color', 'green');
            } catch (error) {
                $(this).css('color', 'red');
            }
        });
    };
})
