/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class InvoiceOverviewPage {
  constructor(public expect) {
  }

  async openFirstInvoice(page) {
    await this.waitForInvoiceToBeCreated(page);

    await page.locator('[data-ui-id="sales-order-tabs-tab-content-order-invoices"] table').getByText('View').first().click();

    await this.expect(page).toHaveURL(/sales\/order_invoice\/view\/invoice_id/);
  }

  async waitForInvoiceToBeCreated(page, maxRetries = 20) {
    const viewLink = page.locator('[data-ui-id="sales-order-tabs-tab-content-order-invoices"] table').getByText('View').first();

    for (let attempt = 0; attempt < maxRetries; attempt++) {
      await this.openInvoicesTab(page);

      try {
        await viewLink.waitFor({ timeout: 3000 });
        return;
      } catch (error) {
        await page.reload({ waitUntil: 'load' });
      }
    }

    throw new Error(`No invoice was created for the order after ${maxRetries} retries.`);
  }

  async openInvoicesTab(page) {
    await page.locator('[data-ui-id="sales-order-tabs-tab-sales-order-view-tabs"] .ui-state-active').waitFor();

    await page.locator('[data-ui-id="sales-order-tabs-tab-item-order-invoices"]').click();

    await page.locator('#sales_order_view_tabs_order_invoices_content [data-role="spinner"]').waitFor({ state: 'hidden' });

    await page.locator('[data-ui-id="sales-order-tabs-tab-content-order-invoices"]').waitFor();
  }
}
