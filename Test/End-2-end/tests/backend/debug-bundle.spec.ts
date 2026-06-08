/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import * as fs from 'fs';

test('Can download a debug bundle from Developer Settings', async ({page}) => {
    await page.goto('/admin/');

    const configLink = page.locator('[data-ui-id="menu-magento-config-system-config"] > a');
    const href = await configLink.getAttribute('href');
    await page.goto(href);

    await page.locator('.config-nav-block._show').waitFor({state: 'visible', timeout: 30000});
    await page.locator('.mollie-tab').click();
    await page.locator('.mollie-tab._show').waitFor({state: 'visible', timeout: 30000});
    await page.locator('.mollie-tab._show a', {hasText: 'Developer Settings'}).click();

    await page.waitForURL(/admin\/system_config\/edit\/section\/mollie_developer_settings/);
    await page.locator('.mollie-tab._show').waitFor({state: 'visible', timeout: 60000});

    const downloadButton = page.locator('#mm-mollie-button_debug-download');
    if (!await downloadButton.isVisible()) {
        await page.locator('#mollie_developer_settings_advanced-head').click();
    }
    await downloadButton.waitFor({state: 'visible', timeout: 10000});

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        downloadButton.click(),
    ]);

    expect(download.suggestedFilename()).toMatch(/^mollie-debug-\d{8}-\d{6}\.tgz$/);

    const filePath = await download.path();
    const {size} = await fs.promises.stat(filePath);
    expect(size).toBeGreaterThan(5 * 1024);
});
