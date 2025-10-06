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

if (Cypress.env('mollie_available_methods').includes('mobilepay')) {
  describe('CCheck that mobilepay behaves as expected', () => {
    [
      {status: 'paid', orderStatus: 'Processing', title: 'C4258546: Validate the submission of an order with Mobile Pay as payment method and payment mark as "Paid"'},
      {status: 'failed', orderStatus: 'Canceled', title: 'C4258547: Validate the submission of an order with Mobile Pay as payment method and payment mark as "Failed"'},
      {status: 'expired', orderStatus: 'Canceled', title: 'C4258548: Validate the submission of an order with Mobile Pay as payment method and payment mark as "Expired"'},
      {status: 'canceled', orderStatus: 'Canceled', title: 'C4258549: Validate the submission of an order with Mobile Pay as payment method and payment mark as "Cancelled"'},
    ].forEach((testCase) => {
      it(testCase.title, () => {
        visitCheckoutPayment.visit();

        cy.intercept('mollie/checkout/redirect/paymentToken/*').as('mollieRedirect');

        checkoutPaymentPage.selectPaymentMethod('Mobile Pay');
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
