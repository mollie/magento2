export default class ShipmentPage {

    changeQuantity(sku, quantity) {
        cy.get('.product-sku-block').contains(sku).parents('tr').find('.col-qty input').clear().type(quantity);
        cy.get('#shipment_comment_text').focus(); // This is needed to trigger the change event
    }

    ship() {
        cy.get('#system_messages').should('have.length.gte', 0);
        cy.get('[data-ui-id="order-items-submit-button"]').should('be.enabled').click();

        cy.get('[data-ui-id="sales-order-tabs-tab-sales-order-view-tabs"] .ui-state-active').should('be.visible');
    }
}
