/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, Page, test} from '@playwright/test';
import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";
import FrontendLogin from "Pages/frontend/FrontendLogin";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutShippingPage = new CheckoutShippingPage();
const frontendLogin = new FrontendLogin();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

const CREATE_SESSION_PATH = '/mollie/express/createSession';

function recordCreateSessionRequests(page: Page): string[] {
  const urls: string[] = [];

  page.on('request', (request) => {
    if (request.url().includes(CREATE_SESSION_PATH)) {
      urls.push(request.url());
    }
  });

  return urls;
}

test('[1186] The express session is not created before a shipping method is selected', async ({ page }) => {
  const requests = recordCreateSessionRequests(page);

  await productPage.openProduct(page, 4);
  await productPage.addSimpleProductToCart(page);

  await checkoutPage.visit(page);

  // Filling the address sets the guest email and reloads the totals. Before the fix the session was created
  // here, while the grand total still excluded the shipping costs.
  await visitCheckoutPayment.fillAddress(page, 'NL');

  expect(
    requests,
    'no express session may be created while the grand total still excludes the shipping costs'
  ).toHaveLength(0);

  await checkoutShippingPage.selectFirstAvailableShippingMethod(page);
  await checkoutPage.continue(page);

  await expect
    .poll(() => requests.length, {
      message: 'the express session must be created once the shipping method is known',
    })
    .toBeGreaterThan(0);

  for (const url of requests) {
    expect(url).toContain(`${CREATE_SESSION_PATH}/type/checkout`);
  }
});

test('[1186] The cart page creates its express session with the cart type', async ({ page }) => {
  const requests = recordCreateSessionRequests(page);

  // The cart placement only creates a session for logged in customers, because a guest has no email on the
  // quote at that point. That is unchanged behaviour, but it means this test cannot run as a guest.
  await frontendLogin.register(page, `express-components-${Date.now()}@mollie.com`, 'Mollie123!');

  await productPage.openProduct(page, 4);
  await productPage.addSimpleProductToCart(page);

  await page.goto('/checkout/cart');

  await expect
    .poll(() => requests.length, {
      message: 'the cart page must create an express session',
    })
    .toBeGreaterThan(0);

  for (const url of requests) {
    expect(
      url,
      'the cart page has no shipping method yet, so it must not use the checkout type'
    ).toContain(`${CREATE_SESSION_PATH}/type/cart`);
  }
});
