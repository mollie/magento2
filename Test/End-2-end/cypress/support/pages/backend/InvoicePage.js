/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class InvoicePage {
    invoice() {
        cy.get('#system_messages').should('have.length.gte', 0);

        // Last element on the page so javascript has time to load
        cy.get('.magento-version').should('be.visible');
        cy.wait(500);

        cy.get('[data-ui-id="order-items-submit-button"]').should('be.enabled').click();

        cy.get('[data-ui-id="sales-order-tabs-tab-sales-order-view-tabs"] .ui-state-active').should('be.visible');

        cy.url().should('include', '/admin/sales/order/view/order_id/');
    }
}
