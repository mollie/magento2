/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {Page, expect} from '@playwright/test';
import BackendLogin from 'Pages/backend/BackendLogin';

const backendLogin = new BackendLogin();

export default class CreateOrderPage {
  async startNewOrder(page: Page) {
    await page.goto('/admin/sales/order/');
    await this.ensureLoggedIn(page, '/admin/sales/order/');

    await page.getByRole('button', {name: 'Create New Order'}).click();
    await page.waitForLoadState('networkidle');
  }

  async selectCustomerByEmail(page: Page, email: string) {
    await page.locator('tr').filter({hasText: email}).first().click();
    await page.waitForLoadState('networkidle');
  }

  async selectStoreView(page: Page, storeView: string) {
    await page.locator('#order-store-selector').getByText(storeView, {exact: true}).click();
    await page.waitForLoadState('networkidle');

    await expect(page.locator('#email')).toBeVisible();
  }

  async addProductBySku(page: Page, sku: string, quantity = 1) {
    await page.locator('#add_products').click();

    const grid = page.locator('#sales_order_create_search_grid');
    await grid.waitFor({state: 'visible'});

    const skuFilter = grid.locator('input[name="sku"]');
    await skuFilter.fill(sku);
    await skuFilter.press('Enter');
    await this.waitForLoadingMask(page);

    const row = grid.locator('tr').filter({hasText: sku}).first();
    await row.locator('input[type="checkbox"]').check();
    await row.locator('input.qty').fill(quantity.toString());

    await page.getByRole('button', {name: 'Add Selected Product(s) to Order'}).click();
    await this.waitForLoadingMask(page);
  }

  async selectFirstShippingMethod(page: Page) {
    await page.getByText('Get shipping methods and rates').click();

    const shippingMethod = page.locator('input[name="order[shipping_method]"]').first();
    await shippingMethod.waitFor({state: 'visible'});
    await shippingMethod.check();

    await this.waitForLoadingMask(page);
  }

  async selectPaymentMethod(page: Page, code: string) {
    const paymentMethod = page.locator(`input[name="payment[method]"][value="${code}"]`);
    await paymentMethod.waitFor({state: 'visible'});
    await paymentMethod.check();

    await this.waitForLoadingMask(page);
  }

  async submitOrder(page: Page) {
    await this.waitForLoadingMask(page);

    const submitButton = page.locator('#submit_order_top_button');
    await expect(submitButton).toBeEnabled();
    await submitButton.click();

    await page.waitForURL('**/sales/order/view/**', {timeout: 60000});
    await expect(page.locator('#order_status')).toBeVisible();
  }

  async getPaymentLinkUrl(page: Page): Promise<string> {
    const link = page.locator('.mollie-checkout-url a').first();
    await link.waitFor({state: 'visible'});

    const href = await link.getAttribute('href');
    if (href === null || href === '') {
      throw new Error('The "Click here to pay" link does not have a href.');
    }

    return href;
  }

  async waitForLoadingMask(page: Page) {
    await page.locator('#loading-mask, .loading-mask')
      .waitFor({state: 'hidden', timeout: 30000})
      .catch(() => {});
  }

  async ensureLoggedIn(page: Page, urlToNavigateAfterLogin: string) {
    const passwordElement = await page.getByText('Forgot your password?').count();

    if (passwordElement === 0) {
      return;
    }

    await backendLogin.login(page);
    await page.goto(urlToNavigateAfterLogin);
  }
}
