/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect, Page } from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import ComponentsAction from "Actions/checkout/ComponentsAction";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import BackendLogin from "Pages/backend/BackendLogin";
import FrontendLogin from "Pages/frontend/FrontendLogin";
import AccountSavedCardsPage from "Pages/frontend/AccountSavedCardsPage";

test.use({
  launchOptions: { args: ['--disable-web-security'] },
  bypassCSP: true,
  storageState: { cookies: [], origins: [] },
});

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const components = new ComponentsAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();
const backendLogin = new BackendLogin();
const frontendLogin = new FrontendLogin();
const accountSavedCardsPage = new AccountSavedCardsPage();

async function registerAndPlaceOrderWithSavedCard(page: Page) {
  const email = `mollie-test-${Date.now()}@example.com`;
  await frontendLogin.register(page, email, 'MollieTest1!');

  await visitCheckoutPayment.visitAsCustomer(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'Credit Card');

  await components.fillComponentsForm(page, 'Mollie Tester', '3782 822463 10005', '1230', '1234');

  await checkoutPaymentPage.checkSaveCard(page);

  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
}

test('Validate placing an order with Credit Card and saving the card for faster checkout', async ({ page }) => {
  await registerAndPlaceOrderWithSavedCard(page);

  await backendLogin.login(page);

  if (mollieHostedPaymentPage.incrementId) {
    await ordersPage.openByIncrementId(page, mollieHostedPaymentPage.incrementId);
  } else {
    await ordersPage.openLatestOrder(page);
  }

  await ordersPage.assertOrderStatusIs(page, 'Processing');
});

test('Validate that a saved card appears at checkout and can be used to place an order', async ({ page }) => {
  test.skip(true, 'Only works in LIVE mode');

  await registerAndPlaceOrderWithSavedCard(page);

  await visitCheckoutPayment.visitAsCustomer(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'Credit Card');

  await checkoutPaymentPage.selectSavedCard(page);

  await checkoutPaymentPage.placeOrder(page);

  await page.waitForURL(/checkout\/onepage\/success|mollie\.com\/checkout/, { timeout: 30000 });

  if (page.url().includes('mollie.com/checkout')) {
    await mollieHostedPaymentPage.selectStatus(page, 'paid');
  }

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
});

test('Validate that a saved card appears in the account and can be deleted', async ({ page }) => {
  test.skip(true, 'Only works in LIVE mode');

  await registerAndPlaceOrderWithSavedCard(page);

  await accountSavedCardsPage.visit(page);

  await accountSavedCardsPage.assertPageIsVisible(page);

  await accountSavedCardsPage.assertHasCard(page, '0005');

  await accountSavedCardsPage.deleteFirstCard(page);

  await accountSavedCardsPage.assertHasNoCards(page);
});

test('Validate that a new card can be used at checkout when a saved card is available', async ({ page }) => {
  test.skip(true, 'Only works in LIVE mode');

  await registerAndPlaceOrderWithSavedCard(page);

  await visitCheckoutPayment.visitAsCustomer(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'Credit Card');

  await checkoutPaymentPage.selectNewCard(page);

  await components.fillComponentsForm(page, 'Mollie Tester', '3782 822463 10005', '1230', '1234');

  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
});
