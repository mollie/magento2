/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import Cookies from "Services/Cookies";

const cookies = new Cookies();

export default class CheckoutPaymentPage {
  selectPaymentMethod(name) {
    cy.contains(name).should('be.visible').click();
  }

  selectIssuer(issuer) {
    cy.contains(issuer).first().check();
  }

  selectFirstAvailableIssuer() {
    cy.get('.payment-method._active [name="issuer"]').first().should('be.visible').check();
  }

  pressPlaceOrderButton() {
    cy.get('.payment-method._active .action.primary.checkout').click();
  }

  enterCouponCode(code = 'H20') {
    cy.contains('Apply Discount Code').click();
    cy.get('[name=discount_code]').should('be.visible').type(code);
    cy.get('.action.action-apply').click();

    cy.get('.totals.discount').should('be.visible');
  }

  placeOrder() {
    cy.intercept('**/mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

    cy.intercept('POST', '**/rest/*/V1/guest-carts/*/payment-information').as('placeOrderAction');

    this.pressPlaceOrderButton();

    cy.wait('@placeOrderAction').then((interception) => {
      cy.expect(interception.response.statusCode).to.eq(200);
      cy.wrap(interception.response.body).as('order-id');
    });

    cookies.disableSameSiteCookieRestrictions();

    cy.wait('@mollieRedirect').then((interception) => {
      cy.visit(interception.response.headers.location);
    });
  }
}
