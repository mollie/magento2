/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import Cookies from "Services/Cookies";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";

const cookies = new Cookies();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();

test('[C1988313] Validate that an order can be placed through REST', async ({ page }) => {
  await page.goto('opt/mollie-pwa-rest.html');

  await page.click('[data-key="start-checkout-process"]');

  await page.click('[data-key="mollie_methods_ideal"]');

  await page.click('[data-key="place-order-action"]');

  const orderId = await page.textContent('[data-key="order-id"]');
  await page.goto(await page.getAttribute('[data-key="redirect-url"]', 'href'));

  await mollieHostedPaymentPage.selectFirstIssuer(page);
  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);

  await cookies.disableSameSiteCookieRestrictions();

  await page.goto(`admin/sales/order/view/order_id/${orderId}`);

  await ordersPage.assertOrderStatusIs(page, 'Processing');
});

test('[C4245493] Validate that a point of sale order can be placed through REST', async ({ page }) => {
  await page.goto('opt/mollie-pwa-rest.html');

  await page.click('[data-key="start-checkout-process"]');

  await page.click('[data-key="mollie_methods_pointofsale"]');

  await page.locator('.terminal-list [type="radio"]').first().check();

  await page.click('[data-key="place-order-action"]');

  const orderId = await page.textContent('[data-key="order-id"]');

  await ordersPage.openOrderById(page, orderId);

  await ordersPage.assertOrderStatusIs(page, 'Pending Payment');
  await page.reload();

  const dataUrl = await page.locator('.change-payment-status span').getAttribute('data-url');
  await page.goto(dataUrl);

  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await ordersPage.openOrderById(page, orderId);

  await ordersPage.assertOrderStatusIs(page, 'Processing');
});
