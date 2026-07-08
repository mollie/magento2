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

  async getOrderSummaryProductNames(page: Page): Promise<string[]> {
    const summary = page.locator('.opc-block-summary');
    await summary.waitFor({ state: 'visible' });

    const toggle = summary.locator('.items-in-cart > .title');
    if (await toggle.count() > 0 && await toggle.getAttribute('aria-expanded') !== 'true') {
      await toggle.click();
    }

    await summary.locator('.product-item .product-item-name').first().waitFor({ state: 'visible' });

    return summary.locator('.product-item .product-item-name').allInnerTexts();
  }
}
