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
