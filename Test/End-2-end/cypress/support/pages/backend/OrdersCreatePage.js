export default class OrdersCreatePage {
    createNewOrderFor(customerName) {
        cy.visit('/admin/sales/order');

        cy.contains('Create New Order').click();

        cy.contains(customerName).click();

        cy.get('.loader').should('not.exist');

        cy.contains(customerName);
    }

    addProduct(productName) {
        cy.get('.action-add').contains('Add Products').click();

        cy.contains(productName).click();

        cy.get('.action-add').contains('Add Selected Product(s) to Order').click();
        cy.get('.order-tables').should('contain', productName);
    }

    selectShippingMethod(method) {
        cy.get('#order-shipping-method-summary').contains('Get shipping methods and rates').click();

        cy.get('#order-shipping-method-choose').contains(method).click();
    }

    submitOrder() {
        cy.get('.actions .save').contains('Submit Order').click();

        cy.url().should('include', 'admin/sales/order/view/order_id/');

        cy.contains('You created the order.');

        cy.get('[name="order_id"]').invoke('val').then(id => {
            cy.wrap(id).as('order-id');
        });
    }
}
