/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import Cookies from "Services/Cookies";

const cookies = new Cookies();

export default class CheckoutPaymentPage {
  orderId = null;

  async selectPaymentMethod(page, name) {
    await page.getByText(name).waitFor({ state: 'visible' });
    await page.click(`text=${name}`);

    // Wait for all elements with the '.loader' class to be detached or invisible
    await page.waitForSelector('[data-role="loader"]:not(:visible)', { state: 'attached' });
  }

  async selectIssuer(page, issuer) {
    await page.locator(`text=${issuer}`).first().check();
  }

  async selectFirstAvailableIssuer(page) {
    await page.locator('.payment-method._active [name="issuer"]').first().waitFor({ state: 'visible' });
    await page.locator('.payment-method._active [name="issuer"]').first().check();
  }

  async pressPlaceOrderButton(page) {
    await page.click('.payment-method._active .action.primary.checkout');
  }

  async enterCouponCode(page, code = 'H20') {
    await page.click('text=Apply Discount Code');
    await page.locator('[name=discount_code]').waitFor({ state: 'visible' });
    await page.fill('[name=discount_code]', code);
    await page.click('.action.action-apply');

    await page.locator('.totals.discount').waitFor({ state: 'visible' });
  }

  async placeOrder(page) {
    await this.pressPlaceOrderButton(page);
  }
}
