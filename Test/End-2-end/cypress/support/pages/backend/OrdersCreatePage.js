export default class OrdersCreatePage {
    createNewOrderFor(customerName) {
        cy.intercept('POST', '**/block/card_validation*').as('header-block');

        cy.visit('/admin/sales/order');

        cy.contains('Create New Order').click();

        cy.contains(customerName).click();

        cy.wait('@header-block');

        cy.get('.loader').should('not.exist');

        cy.contains('Address Information').should('be.visible');
    }

    addProduct(productName) {
        cy.get('.action-add').contains('Add Products').click();

        cy.contains(productName).click();

        cy.get('.action-add').contains('Add Selected Product(s) to Order').click();
        cy.get('.order-tables').should('contain', productName);
    }

    addFirstSimpleProduct() {
        cy.get('#sales_order_create_search_grid_table').should('not.be.visible');

        cy.get('.action-add').contains('Add Products').click();

        cy.wait(1000);

        cy.contains('Account Information').should('be.visible');

        cy.get('#sales_order_create_search_grid_table .action-configure.disabled').first().parents('tr').within(() => {
            cy.get('.checkbox').check();

            cy.get('.qty').first().should('have.value', '1');

            cy.get('.col-name').then(($row) => {
                cy.wrap($row.get(0).innerText).as('product-name');
            });
        });

        cy.get('.action-add').contains('Add Selected Product(s) to Order').click();

        cy.get('@product-name').then((productName) => {
            cy.get('.order-tables').should('contain', productName);
        });
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
