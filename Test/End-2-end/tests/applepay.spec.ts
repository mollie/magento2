/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';

test('[C2033291] Validate that the Apple Pay Develop Merchantid Domain Association file can be loaded', async ({ request }) => {
  const response = await request.get('/.well-known/apple-developer-merchantid-domain-association');
  const body = (await response.text()).trim();

  expect(body).toMatch(/^[0-9a-f]+$/i);

  const association = JSON.parse(Buffer.from(body, 'hex').toString('utf8'));
  expect(association.pspId).toBe('D9C7F701C8C6F2C6F3D656C09944E32200B176F152E58D9140C1C53AA8246E60');
});
