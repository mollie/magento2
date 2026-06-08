/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { Page, expect } from '@playwright/test';

export default class AccountSavedCardsPage {
  async visit(page: Page) {
    await page.goto('/mollie/savedcards/index');
    await page.waitForLoadState('domcontentloaded');
  }

  async assertPageIsVisible(page: Page) {
    await expect(page).toHaveURL(/mollie\/savedcards/);
    await expect(page.locator('.page-title')).toBeVisible();
  }

  async assertHasCard(page: Page, last4: string) {
    await expect(page.locator('.mollie-saved-cards-table')).toContainText(last4);
  }

  async assertHasNoCards(page: Page) {
    await expect(page.locator('.mollie-saved-cards-empty')).toBeVisible();
  }

  async deleteFirstCard(page: Page) {
    page.once('dialog', dialog => dialog.accept());
    await page.locator('.mollie-saved-cards-delete').first().click();
    await page.waitForLoadState('domcontentloaded');
  }

  async assertPageIsHidden(page: Page) {
    await expect(page).toHaveURL(/404|noroute|cms\/noroute/);
  }
}
