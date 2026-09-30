/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, Page, test} from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import Configuration from "Actions/backend/Configuration";

/**
 * https://github.com/mollie/magento2/issues/1101
 */

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const configuration = new Configuration(expect);

const SECTION = 'Payment Methods';
const GROUP = 'Banktransfer';
const INSTRUCTIONS = 'Please pay before we ship your order.';

test.describe.configure({ mode: 'serial' });

const INSTRUCTIONS_ROW = '#row_mollie_payment_methods_mollie_methods_banktransfer_instructions';

const configureStyle = async (page: Page, style: string) => {
  await page.goto('/admin/');
  await configuration.setValue(page, SECTION, GROUP, 'Instructions style', style);
};

const configureInstructions = async (page: Page, instructions: string, style: string) => {
  await configureStyle(page, style);
  await configuration.setTextValue(page, SECTION, GROUP, 'Instructions', instructions);
};

const activeInstructions = (page: Page) => {
  return page.locator('.payment-method._active .mollie-payment-instructions');
};

test.beforeEach(() => {
  test.skip(!process.env.mollie_available_methods.includes('banktransfer'), 'Skipping test as Banktransfer is not available');
});

test.afterAll(async ({ browser }, testInfo) => {
  const context = await browser.newContext(testInfo.project.use);
  await configureStyle(await context.newPage(), 'none');
  await context.close();
});

test('The instructions are shown with the configured message style when Banktransfer is selected', async ({ page }) => {
  await configureInstructions(page, INSTRUCTIONS, 'warning');

  await visitCheckoutPayment.visit(page);
  await checkoutPaymentPage.selectPaymentMethodByCode(page, 'mollie_methods_banktransfer');

  await expect(activeInstructions(page)).toHaveText(INSTRUCTIONS);
  await expect(activeInstructions(page)).toHaveClass(/\bmessage\b/);
  await expect(activeInstructions(page)).toHaveClass(/\bwarning\b/);
});

test('The instructions are shown without message styling when plain text is configured', async ({ page }) => {
  await configureInstructions(page, INSTRUCTIONS, 'plain');

  await visitCheckoutPayment.visit(page);
  await checkoutPaymentPage.selectPaymentMethodByCode(page, 'mollie_methods_banktransfer');

  await expect(activeInstructions(page)).toHaveText(INSTRUCTIONS);
  await expect(activeInstructions(page)).not.toHaveClass(/\bmessage\b/);
});

test('Other payment methods do not show the Banktransfer instructions', async ({ page }) => {
  test.skip(!process.env.mollie_available_methods.includes('ideal'), 'Skipping test as iDEAL is not available');
  await configureInstructions(page, INSTRUCTIONS, 'info');

  await visitCheckoutPayment.visit(page);
  await checkoutPaymentPage.selectPaymentMethodByCode(page, 'mollie_methods_ideal');

  await expect(activeInstructions(page)).toHaveCount(0);
});

test('No instructions hides the field in the admin and shows nothing in the checkout', async ({ page }) => {
  await configureInstructions(page, INSTRUCTIONS, 'info');
  await configureStyle(page, 'none');

  await page.locator('.section-config', { hasText: GROUP }).click();
  await expect(page.locator(INSTRUCTIONS_ROW)).toHaveCount(1);
  await expect(page.locator(INSTRUCTIONS_ROW)).toBeHidden();

  await visitCheckoutPayment.visit(page);
  await checkoutPaymentPage.selectPaymentMethodByCode(page, 'mollie_methods_banktransfer');

  await expect(activeInstructions(page)).toHaveCount(0);
});
