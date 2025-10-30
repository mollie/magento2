/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect} from "@playwright/test";
import BackendLogin from "Pages/backend/BackendLogin";

const backendLogin = new BackendLogin();

export default class OrdersPage {
    async openLatestOrder(page) {
      let url = `/admin/sales/order/index/`;
      await page.goto(url);

      await this.checkIfLoggedIn(page, url);

      await page.locator('.data-grid-actions-cell').first().click();
    }

    async openOrderById(page, id: int) {
      let url = `/admin/sales/order/view/order_id/${id}`;
      await page.goto(url);

      await this.checkIfLoggedIn(page, url);
    }

    async openByIncrementId(page, incrementId: string) {
      let url = `/admin/sales/order/`;
      await page.goto(url);

      await this.checkIfLoggedIn(page, url);

      // Find a th.data-grid-th with the text "Purchase Date" and check if it has the class "_descend". If not, click it.
      const purchaseDate = await page.locator('th.data-grid-th', { hasText: 'Purchase Date' });
      if (!purchaseDate.locator('._descend')) {
        await purchaseDate.click();
      }

      const row = await page.locator('.data-row', { hasText: incrementId })
        .locator('a.action-menu-item', { hasText: 'View' });

      const href = await row.getAttribute('href');
      await page.goto(href);

      await page.getByText('Submit Comment').isVisible();
    }

    async callFetchStatus(page, attempt = 0) {
      await expect(await page.getByRole('button', { name: 'Fetch Status' })).toBeVisible();

      try {
        await page.locator('.fetch-mollie-payment-status').click({timeout: 5000});
      } catch (error) {
        if (attempt > 2) {
          throw error;
        }

        await page.reload();
        await this.callFetchStatus(page, attempt + 1);
        return;
      }

      await expect(await page.locator('.fetch-mollie-payment-status')).toContainText('Fetching...');
      await expect(await page.locator('.fetch-mollie-payment-status')).toContainText('Fetch Status', {timeout: 15000});

      await page.reload();
    }

    async assertOrderStatusIs(page, status: string, maxRetries = 90) {
      const orderStatusLocator = page.locator('#order_status');

      for (let i = 0; i < maxRetries; i++) {
        const text = await orderStatusLocator.textContent();
        if (text && text.includes(status)) {
          await expect(orderStatusLocator).toContainText(status, { timeout: 100 });
          return;
        }
        await page.reload();
        await page.waitForTimeout(1000);
      }

      throw new Error(`Order status "${status}" was not found after ${maxRetries} retries.`);
    }

    async checkIfLoggedIn(page, urlToNavigateAfterLogin) {
      const issueElement = Array.from(await page.getByText('Report an issue').all()).length;
      const passwordElement = Array.from(await page.getByText('Forgot your password?').all()).length;

      if (issueElement === 0 && passwordElement === 1) {
        await backendLogin.login(page);

        await page.goto(urlToNavigateAfterLogin);
      }
    }
}
