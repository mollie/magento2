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
      await expect(page.getByRole('button', { name: 'Fetch Status' })).toBeVisible();

      try {
        await page.locator('.fetch-mollie-payment-status').click({timeout: 5000});
      } catch (error) {
        if (attempt > 2) {
          throw error;
        }

        await page.reload();
        await page.waitForLoadState('load');
        await this.callFetchStatus(page, attempt + 1);
        return;
      }

      await expect(page.locator('.fetch-mollie-payment-status')).toContainText('Fetching...');
      await expect(page.locator('.fetch-mollie-payment-status')).toContainText('Fetch Status', {timeout: 15000});

      await page.waitForLoadState('networkidle');
      await page.reload({waitUntil: 'load'});
    }

    async assertOrderStatusIs(page, status: string, maxRetries = 90) {
      const orderStatusLocator = page.locator('#order_status');

      for (let i = 0; i < maxRetries; i++) {
        const text = await orderStatusLocator.textContent();
        if (text && text.includes(status)) {
          await expect(orderStatusLocator).toContainText(status, { timeout: 100 });
          return;
        }
        await page.reload({waitUntil: 'load'});
        await page.waitForLoadState('domcontentloaded');
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

    async openTab(page, tabName: string) {
        await page.locator('[data-ui-id="sales-order-tabs-tab-sales-order-view-tabs"] li')
            .filter({ hasText: tabName })
            .first()
            .click();
    }
}
