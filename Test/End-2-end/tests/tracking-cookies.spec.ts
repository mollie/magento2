/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);

test('Tracking cookies — _ga client id is forwarded to the success URL', async ({ page, context }) => {
  // Drop a synthetic _ga cookie before any checkout activity. The default
  // tracking-cookies config maps _ga → ?clientId=<raw cookie value> on the success URL.
  const baseUrl = new URL(page.url() === 'about:blank' ? (process.env.BASE_URL ?? 'http://localhost') : page.url());
  await context.addCookies([{
    name: '_ga',
    value: 'GA1.2.111222333.444555666',
    domain: baseUrl.hostname,
    path: '/',
  }]);

  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');
  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectFirstIssuer(page);
  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  // After the merchant return, the URL should carry the raw GA client id.
  await expect(page).toHaveURL(/[?&]clientId=GA1\.2\.111222333\.444555666(?:&|$)/);
});
