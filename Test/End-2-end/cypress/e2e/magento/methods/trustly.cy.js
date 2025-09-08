/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

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

// Disabled as the testmode for Trustly has changed and it not supported right now.
if (Cypress.env('mollie_available_methods').includes('trustly')) {
  describe('Check that trustly behaves as expected', () => {
    [
      {status: 'paid', orderStatus: 'Processing', title: 'C3385963: Validate the submission of an order with Trustly as payment method and payment mark as "Paid"'},
      {status: 'failed', orderStatus: 'Canceled', title: 'C3385964: Validate the submission of an order with Trustly as payment method and payment mark as "Failed"'},
      {status: 'expired', orderStatus: 'Canceled', title: 'C3385965: Validate the submission of an order with Trustly as payment method and payment mark as "Expired"'},
      {status: 'canceled', orderStatus: 'Canceled', title: 'C3385966: Validate the submission of an order with Trustly as payment method and payment mark as "Canceled" '},
    ].forEach((testCase) => {
      it(testCase.title, () => {
        visitCheckoutPayment.visit();

        cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

        checkoutPaymentPage.selectPaymentMethod('Trustly');
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

        ordersPage.assertOrderStatusIs(testCase.orderStatus);
      });
    });
  })
}
