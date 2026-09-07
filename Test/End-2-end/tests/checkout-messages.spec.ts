/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, Page, test} from '@playwright/test';
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import Configuration from "Actions/backend/Configuration";

/**
 * https://github.com/mollie/magento2/issues/1075
 */

const checkoutPage = new CheckoutPage();
const checkoutPaymentPage = new CheckoutPaymentPage();
const checkoutShippingPage = new CheckoutShippingPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const configuration = new Configuration(expect);

const REDIRECT_ON_FAILURE_FIELD = 'Redirect user when redirect fails';

/**
 * Adds a product to the cart without JavaScript, so `Cart\Add::_goBack()` redirects back to the checkout and the
 * success message ends up in the shared `mage-messages` cookie with nothing on the checkout page to render it.
 */
const submitNonAjaxAddToCart = async (page: Page, productId: number) => {
  await page.evaluate((id) => {
    const formKey = document.cookie.match(/(?:^|; )form_key=([^;]+)/)[1];
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/checkout/cart/add/product/' + id + '/';
    form.innerHTML = '<input name="form_key" value="' + formKey + '">' +
      '<input name="product" value="' + id + '">' +
      '<input name="qty" value="1">';
    document.body.appendChild(form);
    form.submit();
  }, productId);

  await page.waitForFunction(() => document.cookie.includes('mage-messages='));
};

const returnToPaymentStep = async (page: Page) => {
  await checkoutPage.visit(page);
  await checkoutShippingPage.selectFirstAvailableShippingMethod(page);
  await checkoutPage.continue(page);
};

const paymentMethodMessages = (page: Page) => {
  return page.locator('.payment-method._active [data-role="checkout-messages"]');
};

const returnFromMollieWithStatus = async (page: Page, status: string) => {
  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');
  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectFirstIssuer(page);
  await mollieHostedPaymentPage.selectStatus(page, status);

  await paymentMethodMessages(page).waitFor({ state: 'visible' });
};

const withRedirectToPaymentStep = async (page: Page, scenario: () => Promise<void>) => {
  await page.goto('/admin/');
  await configuration.setValue(
    page,
    'Order Management',
    'Fallback',
    REDIRECT_ON_FAILURE_FIELD,
    'redirect_to_checkout_payment'
  );

  try {
    await scenario();
  } finally {
    await page.goto('/admin/');
    await configuration.setValue(
      page,
      'Order Management',
      'Fallback',
      REDIRECT_ON_FAILURE_FIELD,
      'redirect_to_cart'
    );
  }
};

test('A message from an unrelated controller is not rendered in the payment method', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await submitNonAjaxAddToCart(page, 4);
  await returnToPaymentStep(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');

  await expect(paymentMethodMessages(page)).toBeHidden();
});

test('A message from an unrelated controller is left for the page it belongs to', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await submitNonAjaxAddToCart(page, 4);
  await returnToPaymentStep(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');
  await page.goto('/checkout/cart');

  await expect(page.locator('.page.messages .message-success')).toContainText('shopping cart');
  await expect(page.locator('.page.messages .message-success a')).toHaveCount(1);
});

test('The payment method shows no messages when nothing produced one', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDEAL | Wero');

  await expect(paymentMethodMessages(page)).toBeHidden();
});

test('A failed transaction is reported as an error in the payment method', async ({ page }) => {
  await withRedirectToPaymentStep(page, async () => {
    await returnFromMollieWithStatus(page, 'failed');

    await expect(paymentMethodMessages(page).locator('.message-error')).toHaveCount(1);

    await page.goto('/checkout/cart');

    await expect(page.locator('.page.messages .message-error')).toHaveCount(0);
  });
});

test('A canceled transaction is reported as a notice in the payment method', async ({ page }) => {
  await withRedirectToPaymentStep(page, async () => {
    await returnFromMollieWithStatus(page, 'canceled');

    await expect(paymentMethodMessages(page).locator('.message-notice')).toHaveCount(1);
    await expect(paymentMethodMessages(page).locator('.message-error')).toHaveCount(0);
  });
});
