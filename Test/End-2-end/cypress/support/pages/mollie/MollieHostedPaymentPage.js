export default class MollieHostedPaymentPage {
    setCookie() {
        cy.setCookie(
            'SESSIONID',
            "cypress-dummy-value",
            {
                domain: '.www.mollie.com',
                sameSite: 'None',
                secure: true,
                httpOnly: true
            }
        );

        cy.reload();
    }

    selectStatus(status) {
        this.setCookie();

        cy.origin('https://www.mollie.com', {args: {status}}, ({status}) => {
            cy.url().should('include', 'https://www.mollie.com/checkout/');

            cy.get('input[value="' + status + '"]').click();

            cy.get('.button').click();
        });
    }

    selectPaymentMethod(method) {
        this.setCookie();

        cy.origin('https://www.mollie.com', {args: {method}}, ({method}) => {
            cy.get('.payment-method-list').contains(method).click();
        });
    }

    selectFirstIssuer() {
        this.setCookie();

        cy.origin('https://www.mollie.com', () => {
            cy.get('.payment-method-list').find('[name="issuer"]').first().click();
        });
    }
}
