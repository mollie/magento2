export default class ProductPage {
    /**
     * @param {string} productId
     */
    openProduct(productId) {
        cy.visit('/catalog/product/view/id/' + productId);
    }

    addSimpleProductToCart(quantity = 1) {
      cy.get('#qty').clear().type(quantity);

      cy.get('#search').focus();

      cy.get('.action.tocart.primary').should('be.enabled').click();

      cy.get('.counter.qty').should('contain', quantity);
    }
}
