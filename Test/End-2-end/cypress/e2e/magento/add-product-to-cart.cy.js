import Cookies from "Services/Cookies";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const cookies = new Cookies();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

describe('Check if the payment methods are available', () => {
  it('C3023: Validate that the iDEAL issuer list available in payment selection', () => {
    visitCheckoutPayment.visit();

    cy.contains('iDeal').should('be.visible').click();

    cy.get('#mollie_methods_ideal-form [name="issuer"]').should('have.length.at.least', 1);
  });
})
