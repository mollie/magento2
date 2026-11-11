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

// Each placement creates its own sessions: adding a product to the cart already fires a product placement
// session, so a test must only observe the placement it is about.
function recordCreateSessionRequests(page: Page, type: string): string[] {
  const urls: string[] = [];

  page.on('request', (request) => {
    if (request.url().includes(`${CREATE_SESSION_PATH}/type/${type}`)) {
      urls.push(request.url());
    }
  });

  return urls;
}

test('[1186] The express session is not created before a shipping method is selected', async ({ page }) => {
  const requests = recordCreateSessionRequests(page, 'checkout');

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
});

test('[1186] The cart page creates its express session with the cart type', async ({ page }) => {
  const requests = recordCreateSessionRequests(page, 'cart');

  // The cart placement only creates a session for logged in customers, because a guest has no email on the
  // quote at that point. That is unchanged behaviour, but it means this test cannot run as a guest.
  await frontendLogin.register(page, `express-components-${Date.now()}@mollie.com`, 'Mollie123!');

  await productPage.openProduct(page, 4);
  await productPage.addSimpleProductToCart(page);

  await page.goto('/checkout/cart');

  await expect
    .poll(() => requests.length, {
      message: 'the cart page must create an express session with the cart type, not the checkout type',
    })
    .toBeGreaterThan(0);
});

test('[1220] The cart page creates an express session for a guest, as Mollie collects the email', async ({ page }) => {
  const requests = recordCreateSessionRequests(page, 'cart');

  await productPage.openProduct(page, 4);
  await productPage.addSimpleProductToCart(page);

  await page.goto('/checkout/cart');

  await expect
    .poll(() => requests.length, {
      message: 'a guest on the cart page must get an express session without entering an email first',
    })
    .toBeGreaterThan(0);
});

test('[1220] The shipping step creates an express session before the address is entered', async ({ page }) => {
  const requests = recordCreateSessionRequests(page, 'shipping');

  await productPage.openProduct(page, 4);
  await productPage.addSimpleProductToCart(page);

  await checkoutPage.visit(page);

  await expect(page.locator('#express-component-shipping')).toBeAttached();

  await expect
    .poll(() => requests.length, {
      message: 'the shipping step must create an express session so Mollie can collect the address',
    })
    .toBeGreaterThan(0);
});

test('[1220] The product page creates an express session for the selected product and quantity', async ({ page }) => {
  const requests: string[] = [];
  page.on('request', (request) => {
    if (request.url().includes(`${CREATE_SESSION_PATH}/type/product`)) {
      requests.push(request.postData() ?? '');
    }
  });

  await productPage.openProduct(page, 4);

  await expect
    .poll(() => requests.length, {
      message: 'opening a product page must create an express session without adding to the cart',
    })
    .toBeGreaterThan(0);

  expect(requests[0]).toContain('qty=1');

  await page.fill('#qty', '2');
  await page.locator('#qty').dispatchEvent('change');

  await expect
    .poll(() => requests.some((data) => data.includes('qty=2')), {
      message: 'changing the quantity must create a session for the new quantity',
    })
    .toBe(true);
});
