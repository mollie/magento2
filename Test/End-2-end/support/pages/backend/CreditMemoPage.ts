/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class CreditMemoPage {
  constructor(public expect) {
  }

  async refund(page) {
    await this.expect(page.getByText('Update Totals')).toBeDisabled();

    await page.waitForTimeout(1000);

    await page.click('.primary.refund');

    // Refunding can take a moment
    await this.expect(page).toHaveURL(/sales\/order\/view\/order_id/, { timeout: 30000 });

    await this.expect(page.locator('[data-ui-id="messages-message-success"]')).toHaveText('You created the credit memo.');

    await this.expect(page.locator('#order_history_block .note-list')).toHaveText(/We refunded/);
    await this.expect(page.locator('#order_history_block .note-list')).toHaveText(/Transaction ID:/);
  }
}
