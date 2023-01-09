export default class ProductPage {
    /**
     * @param {string} productId
     */
    openProduct(productId) {
        cy.visit('/catalog/product/view/id/' + productId);
    }

    addSimpleProductToCart() {
      cy.get('.action.tocart.primary').should('be.enabled').click();

      // TODO: Make this dynamic
      cy.get('.counter.qty').should('contain', 1);
    }
}
