export default class CartPage {
    assertCartPageIsShown() {
        cy.url().should('include', 'checkout/cart');
    }
}
