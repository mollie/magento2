/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test as setup, expect } from '@playwright/test';
import BackendLogin from 'Pages/backend/BackendLogin';
import path from 'path';

const backendLogin = new BackendLogin();

const authFile = path.join(__dirname, '../../.auth/backend.json');

setup('[C4212591] authenticate', async ({ page }) => {
  await backendLogin.login(page);

  await page.context().storageState({ path: authFile });
});
