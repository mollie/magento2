/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
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

test.describe('Check that extra validations for Billie are working as expected', () => {
  test('[C849727] Validate that a company is required to place an order with Billie', async ({ page }) => {
    test.skip(!process.env.mollie_available_methods.includes('billie'), 'Skipping test as Billie is not available');

    await visitCheckoutPayment.visit(page, 'german-shipping-address-without-company.json');

    await checkoutPaymentPage.selectPaymentMethod(page, 'Billie');
    await checkoutPaymentPage.pressPlaceOrderButton(page);

    await expect(page.locator('.message.message-error.error')).toHaveText('Please enter a company name.');
    await expect(page.locator('.message.message-error.error')).toBeVisible();
  });
})

test.describe('Check that Billie behaves as expected', () => {
    const testCases = [
      {status: 'paid', orderStatus: 'Processing', title: '[C363473] Validate the submission of an order with Billie as payment method and payment mark as "Paid"'},
      {status: 'failed', orderStatus: 'Canceled', title: '[C363474] Validate the submission of an order with Billie as payment method and payment mark as "Failed"'},
      {status: 'expired', orderStatus: 'Canceled', title: '[C363476] Validate the submission of an order with Billie as payment method and payment mark as "Expired"'},
      {status: 'canceled', orderStatus: 'Canceled', title: '[C363475] Validate the submission of an order with Billie as payment method and payment mark as "Cancelled"'},
    ];

  for (const testCase of testCases) {
    test(testCase.title, async ({ page }) => {
      test.skip(!process.env.mollie_available_methods.includes('billie'), 'Skipping test as Billie is not available');

      await visitCheckoutPayment.visit(page, 'DE');

      await checkoutPaymentPage.selectPaymentMethod(page, 'Billie');
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
});
