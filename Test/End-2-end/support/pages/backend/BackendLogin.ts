/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect} from "@playwright/test";

export default class BackendLogin {
  async login(page) {
    await page.goto('/admin/');

    const username = 'exampleuser';
    const password = 'examplepassword123';

    await page.goto('/admin');
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Sign in' }).click();

    await page.waitForURL('**/admin/dashboard/**');

    await expect(page.getByRole('link', { name: 'Most Viewed Products' })).toBeVisible();
  }
}
