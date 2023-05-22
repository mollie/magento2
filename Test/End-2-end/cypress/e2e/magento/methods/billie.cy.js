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

describe('Check that extra validations for Billie are working as expected', () => {
  it('C849727: Validate that a company is required to place an order with Billie', () => {
    visitCheckoutPayment.visit('german-shipping-address-without-company.json');

    cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

    checkoutPaymentPage.selectPaymentMethod('Billie');
    checkoutPaymentPage.pressPlaceOrderButton();

    cy.get('.message.message-error.error').contains('Please enter a company name.').should('be.visible');
  });
})

describe('Check if Billie behaves as expected', () => {
  [
    {status: 'authorized', orderStatus: 'Processing', title: 'C363473: Validate the submission of an order with Billie as payment method and payment mark as "Authorized"'},
    {status: 'failed', orderStatus: 'Canceled', title: 'C363474: Validate the submission of an order with Billie as payment method and payment mark as "Failed"'},
    {status: 'expired', orderStatus: 'Canceled', title: 'C363476: Validate the submission of an order with Billie as payment method and payment mark as "Expired"'},
    {status: 'canceled', orderStatus: 'Canceled', title: 'C363475: Validate the submission of an order with Billie as payment method and payment mark as "Cancelled"'},
  ].forEach((testCase) => {
    it(testCase.title, () => {
      visitCheckoutPayment.visit('DE');

      cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

      checkoutPaymentPage.selectPaymentMethod('Billie');
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
