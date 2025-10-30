/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class Configuration {
  expect;

  constructor(expect) {
    this.expect = expect;
  }

  async setValue(page, section, group, field, value) {
    // Click on the config menu and navigate to the link
    const configLink = await page.locator('[data-ui-id="menu-magento-config-system-config"] > a');
    const href = await configLink.getAttribute('href');
    await page.goto(href);

    await this.expect(page.locator('.config-nav-block._show')).toBeVisible();

    // Click on the specified section within the Mollie tab
    await page.locator('.mollie-tab').click();
    await page.locator('.mollie-tab').locator(`text=${section}`).click();

    // Check the URL to confirm navigation to the correct section
    await this.expect(page).toHaveURL(/admin\/system_config\/edit\/section\/mollie_/);

    await this.expect(page.locator('.config-nav-block._show')).toBeVisible();

    // Wait for JavaScript and content to load
    await page.waitForTimeout(1000);
    await page.locator('.mollie-tab._show').waitFor({ state: 'visible', timeout: 60000 });

    // Expand the specific group if not already open
    const groupElement = await page.locator('.section-config', { hasText: group });
    const hasOpenClass = await groupElement.evaluate(el => el.classList.contains('open'));
    if (!hasOpenClass) {
      await groupElement.click();
    }

    await groupElement.locator('.form-list tr').getByLabel(field).selectOption(value);

    await page.click('#save');

    // Ensure the settings save and reload correctly
    await page.locator('.mollie-tab').waitFor({ state: 'visible' });
    await this.expect(await page.getByText('You saved the configuration.')).toBeVisible();
  }
}
