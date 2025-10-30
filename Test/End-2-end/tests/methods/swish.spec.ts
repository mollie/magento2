/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import CartPage from "Pages/frontend/CartPage";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();
const cartPage = new CartPage();

const testCases = [
  {status: 'paid', orderStatus: 'Processing', title: '[C4235811] Validate the submission of an order with Swish as payment method and payment mark as "Paid"'},
  {status: 'failed', orderStatus: 'Canceled', title: '[C4235812] Validate the submission of an order with Swish as payment method and payment mark as "Failed"'},
  {status: 'expired', orderStatus: 'Canceled', title: '[C4235813] Validate the submission of an order with Swish as payment method and payment mark as "Expired"'},
  {status: 'canceled', orderStatus: 'Canceled', title: '[C4235814] Validate the submission of an order with Swish as payment method and payment mark as "Canceled"'},
];

for (const testCase of testCases) {
  test(testCase.title, async ({ page }) => {
    test.skip(!process.env.mollie_available_methods.includes('swish'), 'Skipping test as Swish is not available');
    await visitCheckoutPayment.changeCurrencyTo(page, 'SEK');
    await visitCheckoutPayment.visit(page, 'SE');

    await checkoutPaymentPage.selectPaymentMethod(page, 'Swish');
    await checkoutPaymentPage.placeOrder(page);

    await mollieHostedPaymentPage.selectStatus(page, testCase.status);

    if (testCase.status === 'paid') {
      await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
    }

    if (testCase.status === 'canceled') {
      await cartPage.assertCartPageIsShown(page);
    }

    if (checkoutPaymentPage.orderId) {
      await ordersPage.openOrderById(page, checkoutPaymentPage.orderId);
    } else if (mollieHostedPaymentPage.incrementId) {
      await ordersPage.openByIncrementId(page, mollieHostedPaymentPage.incrementId);
    } else {
      await ordersPage.openLatestOrder(page);
    }

    if (testCase.status === 'expired') {
      await ordersPage.callFetchStatus(page);
    }

    await ordersPage.assertOrderStatusIs(page, testCase.orderStatus);
  });
}
