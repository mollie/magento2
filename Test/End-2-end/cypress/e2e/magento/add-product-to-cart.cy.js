import Cookies from "Services/Cookies";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const cookies = new Cookies();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

describe('Check if the payment methods are available', () => {
  [
    {name: 'iDeal', withIssuer: true, title: 'C3043: Validate the submission of an order with iDEAL as payment method and payment mark as "Paid"'},
    {name: 'Bancontact', withIssuer: false, title: 'C3048: Validate the submission of an order with Bancontact as payment method and payment mark as "Paid"'},
  ].forEach((paymentMethod) => {
    it(paymentMethod.title, () => {
      visitCheckoutPayment.visit();

      cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

      checkoutPaymentPage.selectPaymentMethod(paymentMethod.name);

      if (paymentMethod.withIssuer) {
        checkoutPaymentPage.selectFirstAvailableIssuer();
      }

      checkoutPaymentPage.placeOrder();

      cookies.disableSameSiteCookieRestrictions();

      cy.wait('@mollieRedirect').then((interception) => {
        cy.visit(interception.response.headers.location);
      });

      cy.origin('https://www.mollie.com', () => {
        cy.url().should('include', 'https://www.mollie.com/checkout/');

        cy.get('input[value="paid"]').click();

        cy.get('.button').click();
      });

      cy.url().should('include', 'checkout/onepage/success');

      cy.contains('Thank you for your purchase!').should('be.visible');
    });
  });

  it('C3023: Validate that the iDEAL issuer list available in payment selection', () => {
    visitCheckoutPayment.visit();

    cy.contains('iDeal').should('be.visible').click();

    cy.get('#mollie_methods_ideal-form [name="issuer"]').should('have.length.at.least', 1);
  });
})
