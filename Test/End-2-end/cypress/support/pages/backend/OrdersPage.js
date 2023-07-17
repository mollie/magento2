export default class OrdersPage {
    openLatestOrder() {
        cy.visit('/admin/sales/order/index/');

        cy.get('.admin__data-grid-wrap tbody tr:first').contains('View').click();

        cy.get('.fetch-mollie-payment-status').click();
    }

    openOrderById(id) {
        cy.visit('/admin/sales/order/view/order_id/' + id);
    }

    callFetchStatus() {
        cy.get('.fetch-mollie-payment-status').click();

        cy.wait(1000);

        cy.get('.fetch-mollie-payment-status').click();

        cy.wait(1000);
    }

    assertOrderStatusIs(status) {
        cy.get('#order_status').contains(status);
    }

    assertMolliePaymentStatusIs(status) {
        cy.get('.mollie-payment-status').contains(status);
    }

    assertOrderHasInvoice() {
        cy.get('#sales_order_view_tabs_order_invoices').click();

        // Can be really slow
        cy.get('.spinner', {timeout: 30000}).should('not.be.visible');

        cy.get('#sales_order_view_tabs_order_invoices_content tbody').should('be.visible');
        cy.get('#sales_order_view_tabs_order_invoices_content').should('contain', '1 records found');
    }

    assertOrderHasNoInvoices() {
        cy.get('#sales_order_view_tabs_order_invoices').click();

        // Can be really slow
        cy.get('.spinner', {timeout: 30000}).should('not.be.visible');

        cy.get('#sales_order_view_tabs_order_invoices_content tbody').should('be.visible');
        cy.get('#sales_order_view_tabs_order_invoices_content').should('contain', '0 records found');
    }

    ship() {
        cy.get('#order_ship').should('be.enabled').click();
    }
}
