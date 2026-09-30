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

// https://github.com/mollie/magento2/issues/1086
// A link scanner opens the payment link, which starts a Mollie payment that expires after about
// 15 minutes. The expired webhook canceled the order while the payment link itself was still valid.
// The link is limited to one method, so selecting "expired" expires the whole payment instead of
// only the attempt with that method.
test('[1086] Keeps a payment link order open when the Mollie payment expires while the link is still valid', async ({page, browser}) => {
  test.skip(!process.env.mollie_available_methods.includes('bancontact'), 'Skipping test as Bancontact is not available');
  test.setTimeout(480000);

  await createOrderPage.startNewOrder(page);
  await createOrderPage.selectCustomerByEmail(page, 'roni_cost@example.com');
  await createOrderPage.selectStoreView(page, 'Default Store View');

  await createOrderPage.addProductBySku(page, '24-MB05');

  await createOrderPage.selectFirstShippingMethod(page);
  await createOrderPage.selectPaymentMethod(page, 'mollie_methods_paymentlink');
  await createOrderPage.limitPaymentLinkMethods(page, ['bancontact']);

  await createOrderPage.submitOrder(page);

  const paymentLinkUrl = await createOrderPage.getPaymentLinkUrl(page);

  const expiringContext = await browser.newContext();
  const expiringPage = await expiringContext.newPage();

  try {
    await expiringPage.goto(paymentLinkUrl);

    await mollieHostedPaymentPage.selectStatus(expiringPage, 'expired');

    await expiringPage.waitForURL(/https:\/\/www\.mollie\.com\/checkout\/test-mode\/completed/);
  } finally {
    await expiringContext.close();
  }

  const stillValidComment = 'The order stays open because the payment link is valid until';
  await ordersPage.waitForHistoryCommentOrStatus(page, stillValidComment, 'Canceled', 240);

  await expect(page.locator('#order_status')).not.toContainText('Canceled');
  await expect(page.locator('#order_history_block').getByText(stillValidComment)).toHaveCount(1);

  const payingContext = await browser.newContext();
  const payingPage = await payingContext.newPage();

  try {
    await payingPage.goto(paymentLinkUrl);

    await mollieHostedPaymentPage.selectStatus(payingPage, 'paid');

    await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(payingPage);
  } finally {
    await payingContext.close();
  }

  await ordersPage.assertOrderStatusIs(page, 'Processing', 240);
});

test('Shows the Fetch Status button for a payment link order once the customer has opened the link', async ({page, browser}) => {
  test.setTimeout(360000);

  await createOrderPage.startNewOrder(page);
  await createOrderPage.selectCustomerByEmail(page, 'roni_cost@example.com');
  await createOrderPage.selectStoreView(page, 'Default Store View');

  await createOrderPage.addProductBySku(page, '24-MB05');

  await createOrderPage.selectFirstShippingMethod(page);
  await createOrderPage.selectPaymentMethod(page, 'mollie_methods_paymentlink');

  await createOrderPage.submitOrder(page);

  await expect(page.getByText('Payment not started')).toBeVisible();
  await expect(page.locator('.fetch-mollie-payment-status')).toHaveCount(0);

  const paymentLinkUrl = await createOrderPage.getPaymentLinkUrl(page);

  const visitorContext = await browser.newContext();
  const visitorPage = await visitorContext.newPage();

  try {
    await visitorPage.goto(paymentLinkUrl);

    await mollieHostedPaymentPage.assertIsVisible(visitorPage);
  } finally {
    await visitorContext.close();
  }

  await page.reload({waitUntil: 'load'});

  await expect(page.locator('.fetch-mollie-payment-status')).toBeVisible();
  await expect(page.locator('.fetch-mollie-payment-status')).toBeEnabled();
  await expect(page.getByText('Payment not started')).toHaveCount(0);
});

test('Retrieves the latest status from Mollie when clicking the Fetch Status button on a payment link order', async ({page, browser}) => {
  test.setTimeout(180000);

  await createOrderPage.startNewOrder(page);
  await createOrderPage.selectCustomerByEmail(page, 'roni_cost@example.com');
  await createOrderPage.selectStoreView(page, 'Default Store View');

  await createOrderPage.addProductBySku(page, '24-MB05');

  await createOrderPage.selectFirstShippingMethod(page);
  await createOrderPage.selectPaymentMethod(page, 'mollie_methods_paymentlink');

  await createOrderPage.submitOrder(page);

  const paymentLinkUrl = await createOrderPage.getPaymentLinkUrl(page);

  const visitorContext = await browser.newContext();
  const visitorPage = await visitorContext.newPage();

  try {
    await visitorPage.goto(paymentLinkUrl);

    await mollieHostedPaymentPage.assertIsVisible(visitorPage);
  } finally {
    await visitorContext.close();
  }

  await page.reload({waitUntil: 'load'});

  const response = await ordersPage.clickFetchStatus(page);

  expect(response.status()).toBe(200);

  await expect(page.getByText('The latest status from Mollie has been retrieved')).toBeVisible();
});
