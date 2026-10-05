/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {APIRequestContext} from '@playwright/test';

const DEFAULT_STOCK_ID = 1;

export default class SalableQuantity {
  async getForSku(request: APIRequestContext, sku: string): Promise<number> {
    const response = await request.get(
      `/rest/V1/inventory/get-product-salable-quantity/${encodeURIComponent(sku)}/${DEFAULT_STOCK_ID}`,
      {headers: {Authorization: `Bearer ${process.env.admin_token}`}},
    );

    if (!response.ok()) {
      throw new Error(`Unable to get the salable quantity for "${sku}": ${response.status()} ${await response.text()}`);
    }

    return Number(await response.json());
  }
}
