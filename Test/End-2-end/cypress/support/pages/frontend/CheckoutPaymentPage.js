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

  placeOrder() {
    cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

    cy.intercept('POST', 'rest/default/V1/guest-carts/*/payment-information').as('placeOrderAction');

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
