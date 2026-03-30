/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { Page } from '@playwright/test';

export default class ProductPage {
    /**
     * @param {Page} page
     * @param {string} productId
     */
    async openProduct(page: Page, productId: int) {
        await page.goto(`/catalog/product/view/id/${productId}`);
    }

    async addSimpleProductToCart(page: Page, quantity = 1) {
        await page.locator('#qty').clear();
        await page.locator('#qty').fill(quantity.toString());

        await page.locator('#search').focus();

        await Promise.all([
            page.waitForResponse(
                response => response.url().includes('customer/section/load') && response.ok()
            ),
            page.locator('.action.tocart.primary').click(),
        ]);

        await page.locator('[data-block="minicart"] .counter.qty:not(.empty)').waitFor({ state: 'visible' });
        await page.locator('[data-block="minicart"] .counter.qty:not(.empty)').innerText().then(text => {
            if (!text.includes(quantity.toString())) {
                throw new Error(`Expected counter to contain ${quantity}, but it contains ${text}`);
            }
        });
    }
}
