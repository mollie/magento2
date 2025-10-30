/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';

test('[C2033291] Validate that the Apple Pay Develop Merchantid Domain Association file can be loaded', async ({ request }) => {
  const response = await request.get('/.well-known/apple-developer-merchantid-domain-association');
  const body = await response.text();

  expect(body).toMatch(/^7B2270737/);
  expect(body.trim()).toMatch(/265373839353336646432646335323937366561613237663939333566386330353164393963303030303030303030303030227D$/);
});
