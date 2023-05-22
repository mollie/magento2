import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import CartPage from "Pages/frontend/CartPage";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const ordersPage = new OrdersPage();
const cartPage = new CartPage();

// if (Cypress.env('mollie_available_methods').includes('klarnasliceit')) {
  describe('Check that klarnasliceit behaves as expected', () => {
    [
      {status: 'authorized', orderStatus: 'Processing', title: 'C3069: Validate the submission of an order with Klarna Slice It as payment method and payment mark as "Authorized"'},
      {status: 'failed', orderStatus: 'Canceled', title: 'C3070: Validate the submission of an order with Klarna Slice It as payment method and payment mark as "Failed"'},
      {status: 'expired', orderStatus: 'Canceled', title: 'C3072: Validate the submission of an order with Klarna Slice It as payment method and payment mark as "Expired"'},
      {status: 'canceled', orderStatus: 'Canceled', title: 'C3071: Validate the submission of an order with Klarna Slice It as payment method and payment mark as "Cancelled"'},
    ].forEach((testCase) => {
      it(testCase.title, () => {
        visitCheckoutPayment.visit();

        cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

        checkoutPaymentPage.selectPaymentMethod('Klarna Slice It');
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
// }
