export default class CheckoutSuccessPage {
    assertThatOrderSuccessPageIsShown() {
        cy.url().should('include', 'checkout/onepage/success');

        cy.contains('Thank you for your purchase!').should('be.visible');
    }
}
