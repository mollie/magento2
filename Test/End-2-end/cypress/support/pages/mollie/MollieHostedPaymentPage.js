export default class MollieHostedPaymentPage {
    selectStatus(status) {
        cy.origin('https://www.mollie.com', {args: {status}}, ({status}) => {
            cy.url().should('include', 'https://www.mollie.com/checkout/');

            cy.get('input[value="' + status + '"]').click();

            cy.get('.button').click();
        });
    }
}
