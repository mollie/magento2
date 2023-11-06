/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import MagentoRestApi from "Services/MagentoRestApi";

const magentoRestApi = new MagentoRestApi();

export default class InvoiceOverviewPage {
    openByOrderId(orderId) {
        const invoices = magentoRestApi.getInvoicesByOrderId(orderId).then(response => {
            const entityId = response.items[0].entity_id;
            cy.wrap(entityId).as('invoiceId');
            const incrementId = response.items[0].increment_id;
            cy.wrap(incrementId).as('invoiceIncrementId');
        });

        cy.backendLogin();

        cy.get('[data-ui-id="menu-magento-sales-sales-invoice"] a').click({force: true});

        cy.url().should('include', 'admin/sales/invoice/index');

        cy.get('[data-action="grid-filter-expand"]').click();

        cy.get('.action-clear').click({force: true});

        cy.get('@invoiceIncrementId').then(invoiceIncrementId => {
            cy.get('.admin__form-field-label')
                .contains('Invoice')
                .parents('.admin__form-field')
                .find('input')
                .clear()
                .type(invoiceIncrementId);

            cy.get('.admin__data-grid-filters-wrap .action-secondary').click();

            cy.get('.data-grid-cell-content')
                .contains(invoiceIncrementId)
                .first()
                .parents('tr')
                .find('a')
                .click();
        });
    }
}
