/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import Configuration from "Actions/backend/Configuration";

const configuration = new Configuration(expect);
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();

test('[C849728] Validate that each payment methods have a specific CSS class', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDeal');

  await expect(page.locator('.payment-method._active')).toHaveClass(/payment-method-mollie_methods_ideal/);

  const availableMethods = process.env.mollie_available_methods;
  const paymentMethodsList = await page.locator('.payment-method').all();

  for (const element of await paymentMethodsList) {
    const classList = await element.getAttribute('class');

    if (classList.indexOf('payment-method-mollie_methods_') === -1 ||
        // Skip iDeal as it is the selected payment method
        classList.indexOf('payment-method-mollie_methods_ideal') !== -1
    ) {
      continue;
    }

    expect(classList).not.toContain('_active');
  }
});

test('[C849662] Validate that the quote is restored when using the back button ', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDeal');

  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectFirstIssuer(page);
  await mollieHostedPaymentPage.assertIsVisible(page);

  await page.goto('/checkout#payment');

  await expect(page).toHaveURL(/checkout#payment/);
});

test('[C2530311] Validate that the success page can only be visited once', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'iDeal');

  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectFirstIssuer(page);
  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);

  await page.goto('/checkout/cart');

  await expect(page).toHaveURL(/checkout\/cart/);
});
