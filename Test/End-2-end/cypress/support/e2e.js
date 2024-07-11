/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

Cypress.on('uncaught:exception', (error, runnable) => {
    // Errors that sometimes occur but are safe to ignore
    if (error.message.indexOf('Cannot read properties of undefined (reading \'remove\')') !== -1 ||
        error.message.indexOf('Cannot read properties of undefined (reading \'clone\')') !== -1) {
        return false
    }

    // These errors are happing in Magento 2.4.7
    if (error.message.indexOf('$fotoramaElement.fotorama is not a function') !== -1 ||
        error.message.indexOf('You cannot apply bindings multiple times to the same element.') !== -1 ||
        error.message.indexOf('$(...).filter(...).collapse is not a function') !== -1
    ) {
        return false
    }
})
