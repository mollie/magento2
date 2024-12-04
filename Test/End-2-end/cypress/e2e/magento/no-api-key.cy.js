import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

describe('Test without API key', () => {
    it('C4225556: Can still use the webshop when no API key is set and the module is disabled', () => {
        visitCheckoutPayment.visit();

        cy.contains('Ship To:').should('be.visible');
    });
});
