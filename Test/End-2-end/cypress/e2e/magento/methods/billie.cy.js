import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

describe('C849727: Validate that a company is required to place an order with Billie', () => {
  it('Requires the company to be filled before placing the order', () => {
    visitCheckoutPayment.visit('german-shipping-address-without-company.json');

    cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

    checkoutPaymentPage.selectPaymentMethod('Billie');
    checkoutPaymentPage.pressPlaceOrderButton();

    cy.get('.message.message-error.error').contains('Please enter a company name.').should('be.visible');
  });
})
