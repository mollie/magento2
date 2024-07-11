/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class CheckoutShippingPage {
  shouldSkipUsername = false

  skipUsername() {
      this.shouldSkipUsername = true
  }

  fillDutchShippingAddress() {
    cy.fixture('dutch-shipping-address').then((address) => {
      this.fillShippingAddress(address);
    });
  }

  fillGermanShippingAddress() {
    cy.fixture('german-shipping-address').then((address) => {
      this.fillShippingAddress(address);
    });
  }

  fillFrenchShippingAddress() {
    cy.fixture('french-shipping-address').then((address) => {
      this.fillShippingAddress(address);
    });
  }

  fillShippingAddressUsingFixture(fixture) {
    cy.fixture(fixture).then((address) => {
      this.fillShippingAddress(address);
    });
  }

  fillShippingAddress(address) {
    Object.keys(address.type).forEach((field) => {
      if (['username', 'password'].includes(field) && this.shouldSkipUsername) {
          return;
      }

      cy.log('Filling field: ' + field);
      cy.get('#checkout-step-shipping [name="' + field + '"]').type(address.type[field]);
    });

    Object.keys(address.select).forEach((field) => {
      cy.get('#checkout-step-shipping [name="' + field + '"]').select(address.select[field]);
    });

    cy.get('.loader').should('not.exist');
  }

  selectFirstAvailableShippingMethod() {
    cy.get('.table-checkout-shipping-method [type="radio"]').first().check()

    cy.get('.loader').should('not.exist');
  }
}
