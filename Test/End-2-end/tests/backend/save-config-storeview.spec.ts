/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test, type Page} from '@playwright/test';

const storeViewId = 1;
const idealGroup = 'mollie_payment_methods_mollie_methods_ideal';

test('Can save a payment method value at store view scope', async ({page}) => {
    await openPaymentMethodsSectionAtStoreViewScope(page);

    const dialog = await openMethodDialog(page);

    const inherit = dialog.locator(`#${idealGroup}_title_inherit`);
    if (await inherit.isChecked()) {
        await inherit.uncheck();
    }

    await dialog.locator(`#${idealGroup}_title`).fill('iDEAL store view title');
    await saveAndExpectSuccess(page, dialog);

    const dialogAfterSave = await openMethodDialog(page);
    await expect(dialogAfterSave.locator(`#${idealGroup}_title`)).toHaveValue('iDEAL store view title');

    // Restore the inherited value so this test has no side effects on other tests
    await dialogAfterSave.locator(`#${idealGroup}_title_inherit`).check();
    await saveAndExpectSuccess(page, dialogAfterSave);
});

async function openPaymentMethodsSectionAtStoreViewScope(page: Page): Promise<void> {
    await page.goto('/admin/');

    const configLink = page.locator('[data-ui-id="menu-magento-config-system-config"] > a');
    const href = await configLink.getAttribute('href');
    await page.goto(href);

    await page.locator('.config-nav-block._show').waitFor({state: 'visible', timeout: 30000});
    await page.locator('.mollie-tab').click();
    await page.locator('.mollie-tab._show').waitFor({state: 'visible', timeout: 30000});
    await page.locator('.mollie-tab._show a', {hasText: 'Payment Methods'}).click();

    await page.waitForURL(/admin\/system_config\/edit\/section\/mollie_payment_methods/);

    await page.goto(page.url().replace(/\/?$/, '/') + `store/${storeViewId}/`);
}

async function openMethodDialog(page: Page) {
    const cardHead = page.locator(`#${idealGroup}-head`);
    await cardHead.waitFor({state: 'visible', timeout: 60000});
    await cardHead.click();

    const dialog = page.locator(`#${idealGroup}_dialog`);
    await expect(dialog).toBeVisible();

    return dialog;
}

async function saveAndExpectSuccess(page: Page, dialog: ReturnType<Page['locator']>): Promise<void> {
    await dialog.locator('.mollie-payment-save-config').click();

    await expect(page.getByText('You saved the configuration.')).toBeVisible();
    expect(page.url()).toContain(`/store/${storeViewId}/`);
}
