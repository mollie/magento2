/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import ComponentsAction from "Actions/checkout/ComponentsAction";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const components = new ComponentsAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const ordersPage = new OrdersPage();

if (Cypress.env('mollie_available_methods').includes('creditcard')) {
  describe('Check that creditcards with components behaves as expected', () => {
    it('C3037: Validate the submission of an order with Credit Card as payment method using Mollie Components and payment mark as "Paid"', () => {
      visitCheckoutPayment.visit();

      checkoutPaymentPage.selectPaymentMethod('Credit Card');

      components.fillComponentsForm(
        'Mollie Tester',
        '3782 822463 10005',
        '1230',
        '1234'
      );

      checkoutPaymentPage.placeOrder();

      mollieHostedPaymentPage.selectStatus('paid');

      checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

      cy.backendLogin();

      cy.get('@order-id').then((orderId) => {
        ordersPage.openOrderById(orderId);
      });

      ordersPage.assertOrderStatusIs('Processing');
    });
  })
}
