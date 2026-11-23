/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'https://js.mollie.com/v2/mollie.js',
], function ($) {
    'use strict';

    const CHANGE_DELAY_MS = 500;
    const INITIALIZED_KEY = 'mollieExpressInitialized';

    /*
     * The product page has no cart. Every session is created from the add to cart form, so it holds exactly the
     * product, quantity and options the customer selected. A change in the form starts a new session.
     */
    return function (config, element) {
        const form = $(element).closest('form');

        // Luma renders the add to cart block in two places for some product types. One set of buttons is enough.
        if (!form.length || form.data(INITIALIZED_KEY)) {
            element.remove();
            return;
        }

        form.data(INITIALIZED_KEY, true);

        let requestedData = null;
        let requestNumber = 0;
        let changeTimer = null;

        const clear = function () {
            element.innerHTML = '';
        };

        const mount = function (clientAccessToken) {
            clear();

            Mollie.Checkout(clientAccessToken).create('express-component', {
                paymentMethods: {
                    applepay: 'never',
                    googlepay: 'never',
                },
                buttons: {
                    paypal: {
                        visibility: 'hidden',
                    },
                },
            }).mount(element);
        };

        const createSession = function () {
            const data = form.serialize();
            if (data === requestedData) {
                return;
            }

            requestedData = data;
            const currentRequest = ++requestNumber;

            // A product without all its required options cannot be bought yet, so the server answers with an
            // error and the buttons stay hidden until the next change.
            $.ajax({
                global: false,
                type: 'POST',
                url: config.createSessionUrl,
                data: data,
            }).done(function (result) {
                if (currentRequest === requestNumber) {
                    mount(result.clientAccessToken);
                }
            }).fail(function () {
                if (currentRequest === requestNumber) {
                    clear();
                }
            });
        };

        form.on('change input', function () {
            clearTimeout(changeTimer);
            changeTimer = setTimeout(createSession, CHANGE_DELAY_MS);
        });

        createSession();
    };
});
