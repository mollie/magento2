describe('Check the functionality of the payment linkt method', () => {
  it('Is possible to place an order using payment link', () => {
    cy.backendLogin();

    cy.visit('/admin/sales/order');

    cy.contains('Create New Order').click();
  });
});
