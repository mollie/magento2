/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import CreateOrderPage from 'Pages/backend/CreateOrderPage';
import OrdersPage from 'Pages/backend/OrdersPage';
import MollieHostedPaymentPage from 'Pages/mollie/MollieHostedPaymentPage';
import CheckoutSuccessPage from 'Pages/frontend/CheckoutSuccessPage';

const createOrderPage = new CreateOrderPage();
const ordersPage = new OrdersPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);

test('Places an admin order with Mollie Payment Link and processes it after paying through the payment link', async ({page, browser}) => {
  test.setTimeout(360000);

  await createOrderPage.startNewOrder(page);
  await createOrderPage.selectCustomerByEmail(page, 'roni_cost@example.com');
  await createOrderPage.selectStoreView(page, 'Default Store View');

  await createOrderPage.addProductBySku(page, '24-MB05');

  await createOrderPage.selectFirstShippingMethod(page);
  await createOrderPage.selectPaymentMethod(page, 'mollie_methods_paymentlink');

  await createOrderPage.submitOrder(page);

  const paymentLinkUrl = await createOrderPage.getPaymentLinkUrl(page);

  const disconnectedContext = await browser.newContext();
  const paymentPage = await disconnectedContext.newPage();

  try {
    await paymentPage.goto(paymentLinkUrl);

    await mollieHostedPaymentPage.assertIsVisible(paymentPage);
    await mollieHostedPaymentPage.selectPaymentMethod(paymentPage, 'iDEAL | Wero');
    await mollieHostedPaymentPage.selectFirstIssuer(paymentPage);
    await mollieHostedPaymentPage.selectStatus(paymentPage, 'paid');

    await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(paymentPage);
    await checkoutSuccessPage.assertIncrementIdIsShown(paymentPage, mollieHostedPaymentPage.incrementId);
  } finally {
    await disconnectedContext.close();
  }

  await ordersPage.assertOrderStatusIs(page, 'Processing', 240);
});
