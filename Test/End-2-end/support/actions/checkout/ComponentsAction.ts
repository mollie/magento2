/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {Page} from '@playwright/test';

export default class ComponentsAction {
  async fillComponentsForm(page: Page, cardHolder: string, cardNumber: string, expiryDate: string, verificationCode: string) {
    await page.frameLocator('[name="cardHolder-input"]')
      .locator('#cardHolder')
      .fill(cardHolder);

    await page.frameLocator('[name="cardNumber-input"]')
      .locator('#cardNumber')
      .fill(cardNumber);

    await page.frameLocator('[name="expiryDate-input"]')
      .locator('#expiryDate')
      .fill(expiryDate);

    await page.frameLocator('[name="verificationCode-input"]')
      .locator('#verificationCode')
      .fill(verificationCode);
  }
}
