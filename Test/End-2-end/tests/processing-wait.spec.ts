/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import CheckoutPaymentPage from 'Pages/frontend/CheckoutPaymentPage';
import VisitCheckoutPaymentCompositeAction from 'CompositeActions/VisitCheckoutPaymentCompositeAction';
import MollieHostedPaymentPage from 'Pages/mollie/MollieHostedPaymentPage';
import CheckoutSuccessPage from 'Pages/frontend/CheckoutSuccessPage';
import ProcessingWaitPage from 'Pages/frontend/ProcessingWaitPage';

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const processingWaitPage = new ProcessingWaitPage(expect);

test('Shows processing wait page when returning from payment before confirmation, then redirects to success', async ({ page, browser }) => {
  test.skip(!process.env.mollie_available_methods.includes('ideal'), 'Skipping test as iDEAL is not available');

  await visitCheckoutPayment.visit(page);
  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');
  await checkoutPaymentPage.placeOrder(page);
  await mollieHostedPaymentPage.selectFirstIssuer(page);

  const molliePaymentUrl = page.url();

  await mollieHostedPaymentPage.selectStatus(page, 'open');
  await processingWaitPage.assertThatWaitPageIsShown(page);

  // Update the payment to paid from a separate browser context so the simulator
  // does not share the customer's Magento session. In production the Mollie webhook
  // is server-to-server and never touches the customer's session; using a separate
  // context faithfully isolates the two. If both tabs share a session, Magento's
  // success controller (called when the simulator gets redirected to /onepage/success
  // via Process.php) runs clearQuote() and races the first tab's SuccessValidator,
  // bouncing the first tab to /checkout/cart.
  const secondContext = await browser.newContext({ ignoreHTTPSErrors: true });
  const secondPage = await secondContext.newPage();
  await secondPage.goto(molliePaymentUrl);
  await mollieHostedPaymentPage.selectStatus(secondPage, 'paid');
  await secondContext.close();

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
});
