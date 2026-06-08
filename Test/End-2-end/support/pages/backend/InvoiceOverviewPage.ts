/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class InvoiceOverviewPage {
  constructor(public expect) {
  }

  async openFirstInvoice(page) {
    await page.locator('[data-ui-id="sales-order-tabs-tab-sales-order-view-tabs"] .ui-state-active').isVisible();

    await page.waitForTimeout(1000);

    await page.locator('[data-ui-id="sales-order-tabs-tab-item-order-invoices"]').click();

    await page.locator('#sales_order_view_tabs_order_invoices_content [data-role="spinner"]').isHidden();

    await page.locator('[data-ui-id="sales-order-tabs-tab-content-order-invoices"]').waitFor();

    await page.getByText('Order Date').first().isVisible();

    await page.locator('[data-ui-id="sales-order-tabs-tab-content-order-invoices"] table tbody tr').isVisible();
    await page.locator('[data-ui-id="sales-order-tabs-tab-content-order-invoices"] table').getByText('View').first().click();

    await this.expect(page).toHaveURL(/sales\/order_invoice\/view\/invoice_id/);
  }
}
