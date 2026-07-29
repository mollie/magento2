/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

const placeOrderButton = '.payment-method._active .action.primary.checkout';

const isPlaceOrderRequest = (request) =>
  request.method() === 'POST' && new URL(request.url()).pathname.endsWith('/payment-information');

// https://github.com/magmodules/mollie-magento2/issues/1182
// placeOrder() chains the payment token request with .always(), so the Magento order is placed
// even when GET .../mollie/payment-token fails. The token observable is only filled in .done(),
// so afterPlaceOrder() then sends the shopper to mollie/checkout/redirect/paymentToken/undefined.
// The order is left in pending_payment without a Mollie payment ever being created.
test('[1182] A failing payment token request does not place an order', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  // The request carries a jQuery cache buster (?_=<timestamp>), so match on the path with a regex.
  await page.route(/\/mollie\/payment-token/, (route) => route.abort('failed'));

  const placeOrderRequest = page
    .waitForRequest(isPlaceOrderRequest, { timeout: 15000 })
    .catch(() => null);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');
  await checkoutPaymentPage.placeOrder(page);

  expect(await placeOrderRequest).toBeNull();

  expect(page.url()).not.toContain('paymentToken');
  expect(page.url()).toContain('/checkout');

  await expect(page.locator(placeOrderButton)).toBeEnabled();
  await expect(page.locator('.payment-method._active .message-error'))
    .toContainText('We were unable to start your payment');
});
