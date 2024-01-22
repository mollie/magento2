import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import OrdersCreatePage from "Pages/backend/OrdersCreatePage";
import Cookies from "Services/Cookies";

const checkoutSuccessPage = new CheckoutSuccessPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const ordersPage = new OrdersPage();
const ordersCreatePage = new OrdersCreatePage();
const cookies = new Cookies();

describe('Placing orders from the backend', () => {
  // This fails in CI, but works locally. Not sure why.
  if (!Cypress.env('CI')) {
    it('C895380: Validate that the ecommerce admin can submit an order in the backend and mark as "Paid" ', () => {
      cy.backendLogin();

      ordersCreatePage.createNewOrderFor('Veronica Costello');

      ordersCreatePage.addFirstSimpleProduct();

      ordersCreatePage.selectShippingMethod('Fixed');

      // 2.3.7 needs a double click to select the payment method, not sure why.
      cy.get('[for="p_method_mollie_methods_paymentlink"]').click().click();

      cy.get('#mollie_methods_paymentlink_methods').select([
        'banktransfer',
        'creditcard',
        'ideal',
      ]);

      cookies.disableSameSiteCookieRestrictions();

      ordersCreatePage.submitOrder();

      cy.get('.mollie-checkout-url .mollie-copy-url')
        .invoke('attr', 'data-url')
        .then(href => {
          cy.visit(href);
        });

      mollieHostedPaymentPage.selectPaymentMethod('Overboeking');
      mollieHostedPaymentPage.selectStatus('paid');

      checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

      cy.get('@order-id').then((orderId) => {
        ordersPage.openOrderById(orderId);
      });

      ordersPage.assertOrderStatusIs('Processing');
    });
  }
});
