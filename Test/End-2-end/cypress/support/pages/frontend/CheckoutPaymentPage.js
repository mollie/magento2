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

  placeOrder() {
    cy.get('.payment-method._active .action.primary.checkout').click();
  }
}
