/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { Page } from '@playwright/test';

export default class FrontendLogin {
  async login(page: Page, email: string, password: string) {
    await page.goto('/customer/account/login');
    await page.waitForLoadState('domcontentloaded');

    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.getByRole('button', { name: 'Sign In' }).click();

    // Wait until we're no longer on the login page
    await page.waitForFunction(
      () => !window.location.pathname.includes('/login'),
      { timeout: 15000 }
    );
  }

  async register(page: Page, email: string, password: string, firstname: string = 'Mollie', lastname: string = 'Tester') {
    await page.goto('/customer/account/create');
    await page.waitForLoadState('domcontentloaded');

    await page.fill('#firstname', firstname);
    await page.fill('#lastname', lastname);
    await page.fill('#email_address', email);
    await page.fill('#password', password);
    await page.fill('#password-confirmation', password);
    await page.getByRole('button', { name: 'Create an Account' }).click();

    await page.waitForFunction(
      () => !window.location.pathname.includes('/create'),
      { timeout: 15000 }
    );

    await page.goto('/customer/address/new/');
    await page.waitForLoadState('domcontentloaded');

    await page.fill('[name="firstname"]', firstname);
    await page.fill('[name="lastname"]', lastname);
    await page.fill('[name="street[0]"]', 'Doorzonwoning 34A');
    await page.fill('[name="city"]', 'VinexStad');
    await page.fill('[name="postcode"]', '1234AB');
    await page.fill('[name="telephone"]', '0201234567');
    await page.selectOption('[name="country_id"]', 'NL');
    await page.getByRole('button', { name: 'Save Address' }).click();
    await page.waitForLoadState('domcontentloaded');
  }

  async logout(page: Page) {
    await page.goto('/customer/account/logout');
    await page.waitForLoadState('domcontentloaded');
  }
}
