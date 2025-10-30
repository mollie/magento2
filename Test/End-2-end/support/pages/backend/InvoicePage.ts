/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class InvoicePage {
  constructor(public expect) {
  }

  async invoice(page) {
    await this.expect(page.locator('#system_messages')).toHaveCount(0);

    // Last element on the page so javascript has time to load
    await this.expect(page.locator('.magento-version')).toBeVisible();
    await page.waitForTimeout(500);

    await this.expect(page.locator('[data-ui-id="order-items-submit-button"]')).toBeEnabled().then(button => button.click());

    await this.expect(page.locator('[data-ui-id="sales-order-tabs-tab-sales-order-view-tabs"] .ui-state-active')).toBeVisible();

    await this.expect(page.url()).then(url => this.expect(url).toContain('/admin/sales/order/view/order_id/'));
  }

  async creditMemo(page) {
    // Last element on the page so javascript has time to load
    await this.expect(page.locator('.magento-version')).toBeVisible();
    await page.waitForTimeout(500);

    const button = await page.locator('.credit-memo');
    await button.isVisible();
    await button.isEnabled();

    button.click();

    // Increased timeout as this can be slow in CI
    await this.expect(page).toHaveURL(/sales\/order_creditmemo\/new/, { timeout: 30000 });
  }
}
