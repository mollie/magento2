import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import CartPage from "Pages/frontend/CartPage";
import OrdersPage from "Pages/backend/OrdersPage";

const cartPage = new CartPage();
const checkoutPaymentPage = new CheckoutPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const ordersPage = new OrdersPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

if (Cypress.env('mollie_available_methods').includes('blik')) {
  describe('Check if Blik behaves as expected', () => {
    [
      {status: 'paid', orderStatus: 'Processing', title: 'C2775017: Validate the submission of an order with Blik as payment method and payment mark as "Paid"'},
      {status: 'failed', orderStatus: 'Canceled', title: 'C2775018: Validate the submission of an order with Blik as payment method and payment mark as "Failed"'},
      {status: 'expired', orderStatus: 'Canceled', title: 'C2775019: Validate the submission of an order with Blik as payment method and payment mark as "Expired"'},
      {status: 'canceled', orderStatus: 'Canceled', title: 'C2775020: Validate the submission of an order with Blik as payment method and payment mark as "Cancelled"'},
    ].forEach((testCase) => {
      it(testCase.title, () => {
        visitCheckoutPayment.changeCurrencyTo('PLN');
        visitCheckoutPayment.visit();

        cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

        checkoutPaymentPage.selectPaymentMethod('Blik');
        checkoutPaymentPage.placeOrder();

        mollieHostedPaymentPage.selectStatus(testCase.status);

        if (testCase.status === 'paid') {
          checkoutSuccessPage.assertThatOrderSuccessPageIsShown();
        }

        if (testCase.status === 'canceled') {
          cartPage.assertCartPageIsShown();
        }

        cy.backendLogin();

        cy.get('@order-id').then((orderId) => {
          ordersPage.openOrderById(orderId);
        });

        if (testCase.status === 'expired') {
          ordersPage.callFetchStatus();
        }

        ordersPage.assertOrderStatusIs(testCase.orderStatus);
      });
    });
  })
}
