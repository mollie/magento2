export default class OrdersPage {
    openLatestOrder() {
        cy.visit('/admin/sales/order/index/');

        cy.get('.admin__data-grid-wrap tbody tr:first').contains('View').click();

        cy.get('.fetch-mollie-payment-status').click();
    }

    openOrderById(id) {
        cy.visit('/admin/sales/order/view/order_id/' + id);
    }

    callWebhook() {
        cy.url().as('currentUrl');

        cy.get('.mollie-order-id').then((element) => {
            cy.request('/mollie/checkout/webhook?id=' + element.text().trim())
                .then((response) => {
                expect(response.status).to.eq(200)
            });
        });

        cy.get('@currentUrl').then((url) => {
            cy.visit(url);
        });
    }

    assertOrderStatusIs(status) {
        cy.get('#order_status').contains(status);
    }

    assertMolliePaymentStatusIs(status) {
        cy.get('.mollie-payment-status').contains(status);
    }
}
