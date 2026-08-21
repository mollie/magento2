/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import Cookies from "Services/Cookies";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";

const cookies = new Cookies();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();

test('[C1835263] Validate that an order can be placed through GraphQL', async ({ page }) => {
  await page.goto('opt/mollie-pwa-graphql.html');

  await page.click('[data-key="start-checkout-process"]');

  await page.click('[data-key="mollie_methods_ideal"]');

  await page.click('[data-key="place-order-action"]');

  const incrementIdElement = await page.locator('[data-key="increment-id"]');
  const incrementId = await incrementIdElement.textContent();

  await cookies.disableSameSiteCookieRestrictions();

  const redirectUrlElement = await page.locator('[data-key="redirect-url"]');
  const redirectUrl = await redirectUrlElement.getAttribute('href');
  await page.goto(redirectUrl);

  await mollieHostedPaymentPage.selectFirstIssuer(page);
  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);

  if (mollieHostedPaymentPage.incrementId) {
    await ordersPage.openByIncrementId(page, mollieHostedPaymentPage.incrementId);
  } else {
    await ordersPage.openLatestOrder(page);
  }

  await ordersPage.assertOrderStatusIs(page, 'Processing');
});

test('Validate that awaiting_confirmation is reported through GraphQL until the payment is confirmed', async ({ page, browser }) => {
  test.skip(!process.env.mollie_available_methods.includes('ideal'), 'Skipping test as iDEAL is not available');

  await page.goto('opt/mollie-pwa-graphql.html');

  await page.click('[data-key="start-checkout-process"]');

  await page.click('[data-key="mollie_methods_ideal"]');

  await page.click('[data-key="place-order-action"]');

  const redirectUrl = await page.locator('[data-key="redirect-url"]').getAttribute('href');

  // Open Mollie in a separate context so the storefront tab stays alive and keeps the
  // payment token it holds in memory, the same way the demo opens the redirect url in a
  // new tab. Navigating this tab away would lose the token and the scenario with it.
  const mollieContext = await browser.newContext({ ignoreHTTPSErrors: true });
  const molliePage = await mollieContext.newPage();
  await molliePage.goto(redirectUrl);
  await mollieHostedPaymentPage.selectFirstIssuer(molliePage);

  const molliePaymentUrl = molliePage.url();

  // The customer leaves the payment page without paying, so the payment stays open.
  await mollieHostedPaymentPage.selectStatus(molliePage, 'open');
  await molliePage.waitForURL(url => !url.href.includes('mollie.com/checkout'));

  await page.click('[data-key="process-transaction-action"]');

  await expect(page.locator('[data-key="payment-status"]')).toHaveText('OPEN');
  await expect(page.locator('[data-key="awaiting-confirmation"]')).toHaveText('true');
  await expect(page.locator('[data-key="redirect-to-success-page"]')).toHaveText('false');
  await expect(page.locator('[data-key="redirect-to-cart"]')).toHaveText('true');

  // The payment is confirmed afterwards, which is what awaiting_confirmation tells the
  // storefront to wait for instead of dropping the customer back on the cart.
  await molliePage.goto(molliePaymentUrl);
  await mollieHostedPaymentPage.selectStatus(molliePage, 'paid');
  await molliePage.waitForURL(url => !url.href.includes('mollie.com/checkout'));
  await mollieContext.close();

  await page.click('[data-key="process-transaction-action"]');

  await expect(page.locator('[data-key="payment-status"]')).toHaveText('PAID');
  await expect(page.locator('[data-key="awaiting-confirmation"]')).toHaveText('false');
  await expect(page.locator('[data-key="redirect-to-success-page"]')).toHaveText('true');
  await expect(page.locator('[data-key="redirect-to-cart"]')).toHaveText('false');
});

test('[C1835263] Validate that a point of sale order can be placed through GraphQL', async ({ page }) => {
  await page.goto('opt/mollie-pwa-graphql.html');

  await page.click('[data-key="start-checkout-process"]');

  await page.click('[data-key="mollie_methods_pointofsale"]');

  await page.locator('.mollie-terminal').first().check();

  await page.click('[data-key="place-order-action"]');

  const incrementIdElement = await page.locator('[data-key="increment-id"]');
  const incrementId = await incrementIdElement.textContent();

  if (incrementId) {
    await ordersPage.openByIncrementId(page, incrementId);
  }

  await ordersPage.assertOrderStatusIs(page, 'Pending Payment');

  const dataUrl = await page.locator('.change-payment-status span').getAttribute('data-url');
  await page.goto(dataUrl);

  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  if (incrementId) {
    await ordersPage.openByIncrementId(page, incrementId);
  }

  await ordersPage.assertOrderStatusIs(page, 'Processing');
});
