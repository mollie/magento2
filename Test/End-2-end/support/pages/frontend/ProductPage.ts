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

        await page.locator('.action.tocart.primary').click();

        await page.waitForFunction(
            (qty) => {
                const counter = document.querySelector('[data-block="minicart"] .counter.qty');
                return counter?.textContent?.includes(String(qty));
            },
            quantity
        );
    }
}
