/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import MagentoRestApi from "Services/MagentoRestApi";

const magentoRestApi = new MagentoRestApi();

export default class OrdersPage {
    openLatestOrder() {
        cy.visit('/admin/sales/order/index/');

        cy.get('.admin__data-grid-wrap tbody tr:first').contains('View').click();

        cy.get('.fetch-mollie-payment-status').click();
    }

    openOrderById(id) {
        cy.visit('/admin/sales/order/view/order_id/' + id);
    }

    openByIncrementId(incrementId) {
        cy.visit('/admin/sales/order/');

        cy.get('.data-grid-cell-content')
            .contains(incrementId)
            .parents('tr')
            .find('a.action-menu-item')
            .contains('View')
            .then((element) => {
                cy.visit(element.attr('href'));
            });
    }

    callFetchStatus() {
        cy.get('.fetch-mollie-payment-status').click();

        cy.wait(1000);

        cy.get('.fetch-mollie-payment-status').click();

        cy.wait(1000);
    }

    /**
     * 90 seconds is pretty high but test webhooks are considered non-essential so may be slow.
     * @param status
     * @param retries
     */
    assertOrderStatusIs(status, retries = 90) {
      // Webhooks are async. So sometimes we may visit the order page before the status is updated.
      // If that's the case, we reload the page and try again, and do this 3 times.
      cy.get('#order_status').then((element) => {
        if (!element.text().includes(status) && retries > 0) {
          cy.reload();
          cy.wait(1000);

          this.assertOrderStatusIs(status, retries - 1);
          return;
        }

        // Trigger assertion to fail.
        cy.get('#order_status').contains(status, { timeout: 100 });
      });
    }

    assertMolliePaymentStatusIs(status) {
        cy.get('.mollie-payment-status').contains(status);
    }

    assertOrderHasInvoice(orderId, count = 1) {
        magentoRestApi.getInvoicesByOrderId(orderId)
            .then(response => {
                expect(response.total_count).to.equal(count);
            });
    }

    assertOrderHasNoInvoices(orderId) {
        magentoRestApi.getInvoicesByOrderId(orderId)
            .then(response => {
                expect(response.total_count).to.equal(0);
            });
    }

    ship() {
        cy.get('#order_ship').should('be.enabled').click();

        cy.url().should('include', '/admin/order_shipment/new/order_id/');
    }

    invoice() {
        cy.get('#order_invoice').should('be.enabled').click();

        cy.url().should('include', '/admin/sales/order_invoice/new/order_id/');
    }
}
