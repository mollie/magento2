/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { expect, test } from '@playwright/test';
import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);

const productAId = 4;
const productBId = 3;

// https://github.com/mollie/magento2/issues/1025#issuecomment-4898391551
// A pending (but successfully placed) Bank transfer order sets mollie_success = false.
// On the next /checkout entry the observer treats it as an "unsuccessful payment" and
// restores the placed order's quote, discarding the product the customer added afterwards.
test('[1025] Placed pending Bank transfer order does not restore its quote over a new cart', async ({ page }) => {
  test.skip(!process.env.mollie_available_methods.includes('banktransfer'), 'Skipping test as Banktransfer is not available');

  await productPage.openProduct(page, productAId);
  const productAName = await productPage.getProductName(page);

  await productPage.openProduct(page, productBId);
  const productBName = await productPage.getProductName(page);

  await visitCheckoutPayment.visit(page, 'NL', 1, productAId);

  await checkoutPaymentPage.selectPaymentMethod(page, 'banktransfer');
  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectStatus(page, 'open');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);

  await productPage.openProduct(page, productBId);
  await productPage.addSimpleProductToCart(page);

  await checkoutPage.visit(page);

  const productNames = (await checkoutPage.getOrderSummaryProductNames(page)).join(' ');

  expect(productNames).toContain(productBName);
  expect(productNames).not.toContain(productAName);
});
