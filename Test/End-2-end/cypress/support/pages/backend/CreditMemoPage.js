/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class CreditMemoPage {
    refund() {
        cy.get('.primary.refund').click();

        cy.url().should('include', 'admin/sales/order/view/order_id/');

        cy.contains('You created the credit memo.');

        cy.get('.note-list-comment').contains('We refunded').contains('Transaction ID:');
    }
}
