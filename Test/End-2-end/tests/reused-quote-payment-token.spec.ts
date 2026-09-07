/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { expect, test, Page } from '@playwright/test';
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import CartPage from "Pages/frontend/CartPage";

const checkoutPage = new CheckoutPage();
const checkoutPaymentPage = new CheckoutPaymentPage();
const checkoutShippingPage = new CheckoutShippingPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const cartPage = new CartPage();

/**
 * Places a second order while the first payment is still open in another tab. Visiting the
 * checkout restores the quote of the unsuccessful payment, so this order is placed on the
 * very same quote as the first one.
 */
const retryInAnotherTab = async (page: Page) => {
  await checkoutPage.visit(page);

  // The restored quote still holds the address, so the checkout opens on the shipping step.
  await checkoutShippingPage.selectFirstAvailableShippingMethod(page);
  await checkoutPage.continue(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'bancontact');
  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.assertIsVisible(page);
};

// https://github.com/mollie/magento2/issues/1040
// Magento reuses the quote when a customer retries after an unsuccessful payment, so
// AttachPaymentTokenToOrder moved the payment token of the order that was already placed onto
// the new one. The first order was left without a token, and returning from its payment was
// rejected with "Invalid payment token". That exception escaped Checkout\Process, so the
// shopper ended up on an error page without a message and without their cart.
test('[1040] Returning from a failed payment works after retrying in another tab', async ({ page, context }) => {
  test.skip(!process.env.mollie_available_methods.includes('bancontact'), 'Skipping test as Bancontact is not available');

  await visitCheckoutPayment.visit(page, 'BE');

  await checkoutPaymentPage.selectPaymentMethod(page, 'bancontact');
  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.assertIsVisible(page);

  await retryInAnotherTab(await context.newPage());

  await page.bringToFront();
  await mollieHostedPaymentPage.selectStatus(page, 'failed');

  await cartPage.assertCartPageIsShown(page);
  await expect(page.locator('.message-error')).toBeVisible();
});

test('[1040] Returning from a paid payment works after retrying in another tab', async ({ page, context }) => {
  test.skip(!process.env.mollie_available_methods.includes('bancontact'), 'Skipping test as Bancontact is not available');

  await visitCheckoutPayment.visit(page, 'BE');

  await checkoutPaymentPage.selectPaymentMethod(page, 'bancontact');
  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.assertIsVisible(page);

  await retryInAnotherTab(await context.newPage());

  await page.bringToFront();
  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
});
