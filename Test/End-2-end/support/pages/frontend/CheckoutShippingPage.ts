/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { Page } from '@playwright/test';
import * as fs from 'fs/promises';
import * as path from 'path';

export default class CheckoutShippingPage {
  private shouldSkipUsername = false;

  async skipUsername(page: Page) {
    this.shouldSkipUsername = true;
  }

  async fillDutchShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/dutch-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillBelgianShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/belgian-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillGermanShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/german-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillFrenchShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/french-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillSwedishShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/swedish-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillShippingAddressUsingFixture(page: Page, fixture: string) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, `../../../fixtures/${fixture}`), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillShippingAddress(page: Page, address: any) {
    const selectedCountry = await page.locator('#checkout-step-shipping [name="country_id"]').inputValue();

    for (const [field, value] of Object.entries(address.type)) {
      if (['username', 'password'].includes(field) && this.shouldSkipUsername) {
        continue;
      }

      if (field === 'username') {
        await this.fillUsername(page, value as string);
        continue;
      }

      await page.fill(`#checkout-step-shipping [name="${field}"]`, value as string, {timeout: 10000});
    }

    for (const [field, value] of Object.entries(address.select)) {
      await page.selectOption(`#checkout-step-shipping [name="${field}"]`, value as string);

      if (field === 'country_id' && value !== selectedCountry) {
        await page.waitForTimeout(2000);
      }
    }

    await page.waitForSelector('.loader', { state: 'detached' });
  }

  async fillUsername(page: Page, username: string, retry = 0) {
    try {
      // In 2.4.7 the username field is not always loaded. See for more information:
      // https://github.com/magento/magento2/issues/38274
      await page.fill('#checkout-step-shipping [name="username"]', username, {timeout: 10000});
    } catch (e) {
      if (retry > 3) {
        throw new Error('Unable to fill username field after 3 attempts');
      }

      await page.reload();
      await this.fillUsername(page, username, retry + 1);
    }
  }

  async selectFirstAvailableShippingMethod(page: Page) {
    await page.check('.table-checkout-shipping-method [type="radio"]');
    await page.waitForSelector('.loader', { state: 'detached' });
  }
}
