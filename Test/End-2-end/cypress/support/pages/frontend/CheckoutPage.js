export default class CheckoutPage {
  visit() {
    cy.visit('/checkout');

    cy.get('.loader').should('not.exist');
  }

  continue() {
    cy.get('[data-role="opc-continue"]').click();
  }
}
