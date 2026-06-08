/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import VisitCheckoutPaymentCompositeAction from 'CompositeActions/VisitCheckoutPaymentCompositeAction';

const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

test('[C1259056] Validate that Point Of Sale is not shown for a guest user', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await expect(page.locator('[value="mollie_methods_pointofsale"]')).not.toBeVisible();
});
