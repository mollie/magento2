import { Page } from '@playwright/test';

export default class CheckoutPage {
  async visit(page: Page) {
    await page.goto('/checkout');

    // Wait for all elements with the '.loader' class to be detached or invisible
    await page.waitForSelector('.loader:not(:visible)', { state: 'attached' });
  }

  async continue(page: Page) {
    await page.click('[data-role="opc-continue"]');
  }
}
