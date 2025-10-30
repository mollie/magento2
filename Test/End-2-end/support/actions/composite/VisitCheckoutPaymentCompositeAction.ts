/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";
import { Page } from '@playwright/test';

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutShippingPage = new CheckoutShippingPage();

export default class VisitCheckoutPaymentCompositeAction {
  async visit(page: Page, fixture = 'NL', quantity = 1, productId = 4) {
    await productPage.openProduct(page, productId);

    await productPage.addSimpleProductToCart(page, quantity);

    await checkoutPage.visit(page);

    await this.fillAddress(page, fixture);

    await checkoutShippingPage.selectFirstAvailableShippingMethod(page);
    await checkoutPage.continue(page);
  }

  async visitAsCustomer(page: Page, fixture = 'NL', quantity = 1) {
    await productPage.openProduct(page, process.env.DEFAULT_PRODUCT_ID);

    await productPage.addSimpleProductToCart(page, quantity);

    await checkoutPage.visit(page);

    await this.fillAddress(page, fixture, true);

    await checkoutShippingPage.selectFirstAvailableShippingMethod(page);
    await checkoutPage.continue(page);
  }

  async fillAddress(page: Page, fixture: string, asCustomer = false) {
    if (asCustomer) {
      await checkoutShippingPage.skipUsername(page);
    }

    switch (fixture) {
      case 'BE':
        await checkoutShippingPage.fillBelgianShippingAddress(page);
        break;
      case 'DE':
        await checkoutShippingPage.fillGermanShippingAddress(page);
        break;
      case 'NL':
        await checkoutShippingPage.fillDutchShippingAddress(page);
        break;
      case 'FR':
        await checkoutShippingPage.fillFrenchShippingAddress(page);
        break;
      case 'SE':
        await checkoutShippingPage.fillSwedishShippingAddress(page);
        break;
      default:
        await checkoutShippingPage.fillShippingAddressUsingFixture(page, fixture);
    }
  }

  async changeCurrencyTo(page: Page, currency: string) {
    await page.goto('/');

    await page.locator('.greet.welcome').first().waitFor({ state: 'visible' });

    await page.locator('#switcher-currency-trigger span').click();
    await page.locator('.switcher-dropdown').getByText(currency).first().click();
  }

  async changeStoreViewTo(page: Page, name: string) {
    await page.goto('/');

    await page.locator('.greet.welcome').waitFor({ state: 'visible' });

    const switcherTrigger = page.locator('.switcher-trigger');
    const triggerText = await switcherTrigger.textContent();

    if (triggerText && triggerText.includes('Default Store View')) {
      await page.locator('#switcher-language-trigger .view-default').click();
      await page.locator('.switcher-dropdown').getByText(name).click();
    }
  }
}
