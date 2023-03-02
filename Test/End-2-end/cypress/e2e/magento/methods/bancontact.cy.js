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

describe('Check if the payment methods are available', () => {
  [
    // {status: 'paid', orderStatus: 'Processing', title: 'C3048	Validate the submission of an order with Bancontact as payment method and payment mark as "Paid"'},
    // {status: 'open', orderStatus: 'Pending Payment', title: 'C3049	Validate the submission of an order with Bancontact as payment method and payment mark as "Open"'},
    // {status: 'failed', orderStatus: 'Canceled', title: 'C3050	Validate the submission of an order with Bancontact as payment method and payment mark as "Failed"'},
    {status: 'expired', orderStatus: 'Canceled', title: 'C3052	Validate the submission of an order with Bancontact as payment method and payment mark as "Expired"'},
    // {status: 'canceled', orderStatus: 'Canceled', title: 'C3051	Validate the submission of an order with Bancontact as payment method and payment mark as "Cancelled"'},
  ].forEach((testCase) => {
    it(testCase.title, () => {
      visitCheckoutPayment.visit();

      cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

      checkoutPaymentPage.selectPaymentMethod('Bancontact');
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
