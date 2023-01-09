export default class CheckoutShippingPage {
  fillDutchShippingAddress() {
    cy.fixture('dutch-shipping-address').then((address) => {
      this.fillShippingAddress(address);
    });
  }

  fillShippingAddress(address) {
    Object.keys(address.type).forEach((field) => {
      cy.get('.opc-wrapper [name="' + field + '"]').type(address.type[field]);
    });

    Object.keys(address.select).forEach((field) => {
      cy.get('.opc-wrapper [name="' + field + '"]').select(address.select[field]);
    });

    cy.get('.loader').should('not.exist');
  }

  selectFirstAvailableShippingMethod() {
    cy.get('.table-checkout-shipping-method [type="radio"]').first().check()

    cy.get('.loader').should('not.exist');
  }
}
