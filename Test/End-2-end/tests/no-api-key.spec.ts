/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from "@playwright/test";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

test('[C4228281] @no-api-key The checkout keeps working when Mollie is not configured', async ({ page }) => {
  await visitCheckoutPayment.visit(page);

  await expect(page.getByText('Ship To')).toBeVisible();
});
