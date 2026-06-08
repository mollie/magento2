/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect} from "@playwright/test";

export default class CartPage {
    async assertCartPageIsShown(page) {
      await expect(page).toHaveURL('/checkout/cart/');
    }
}
