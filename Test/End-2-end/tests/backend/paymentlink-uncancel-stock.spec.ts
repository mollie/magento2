/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, Page, test} from '@playwright/test';
import CreateOrderPage from 'Pages/backend/CreateOrderPage';
import OrdersPage from 'Pages/backend/OrdersPage';
import MollieHostedPaymentPage from 'Pages/mollie/MollieHostedPaymentPage';
import SalableQuantity from 'Services/SalableQuantity';

const createOrderPage = new CreateOrderPage();
const ordersPage = new OrdersPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const salableQuantity = new SalableQuantity();

const ORDERED_QUANTITY = 3;

type StockScenario = {
  title: string;
  trackedSku: string;
  addProduct: (page: Page) => Promise<void>;
};

const scenarios: StockScenario[] = [
  {
    title: 'simple product',
    trackedSku: '24-UG05',
    addProduct: (page) => createOrderPage.addProductBySku(page, '24-UG05', ORDERED_QUANTITY),
  },
  {
    title: 'configurable product',
    trackedSku: 'MH01-XS-Black',
    addProduct: (page) => createOrderPage.addConfigurableProductBySku(
      page,
      'MH01',
      {Size: 'XS', Color: 'Black'},
      ORDERED_QUANTITY,
    ),
  },
  {
    title: 'bundle product',
    trackedSku: '24-WG084',
    addProduct: (page) => createOrderPage.addBundleProductWithDefaultSelectionsBySku(page, '24-WG080', ORDERED_QUANTITY),
  },
];

test.describe('Stock reservations for uncanceled payment link orders', () => {
  test.describe.configure({mode: 'default'});

  for (const scenario of scenarios) {
    test(`Reserves the stock again when a canceled payment link order with a ${scenario.title} gets paid`, async ({page, browser, request}) => {
      test.setTimeout(360000);

      const initialQuantity = await salableQuantity.getForSku(request, scenario.trackedSku);

      await createOrderPage.startNewOrder(page);
      await createOrderPage.selectCustomerByEmail(page, 'roni_cost@example.com');
      await createOrderPage.selectStoreView(page, 'Default Store View');
      await scenario.addProduct(page);
      await createOrderPage.selectFirstShippingMethod(page);
      await createOrderPage.selectPaymentMethod(page, 'mollie_methods_paymentlink');
      await createOrderPage.submitOrder(page);

      expect(await salableQuantity.getForSku(request, scenario.trackedSku)).toBe(initialQuantity - ORDERED_QUANTITY);

      const paymentLinkUrl = await createOrderPage.getPaymentLinkUrl(page);
      const customerContext = await browser.newContext();
      const customerPage = await customerContext.newPage();

      try {
        await customerPage.goto(paymentLinkUrl);
        await mollieHostedPaymentPage.assertIsVisible(customerPage);

        await ordersPage.cancelOrder(page);
        expect(await salableQuantity.getForSku(request, scenario.trackedSku)).toBe(initialQuantity);

        await mollieHostedPaymentPage.selectPaymentMethod(customerPage, 'iDEAL | Wero');
        await mollieHostedPaymentPage.selectFirstIssuer(customerPage);
        await mollieHostedPaymentPage.selectStatus(customerPage, 'paid');
        await customerPage.waitForURL((url) => url.origin === new URL(paymentLinkUrl).origin);
      } finally {
        await customerContext.close();
      }

      await ordersPage.assertOrderStatusIs(page, 'Processing', 240);

      expect(await salableQuantity.getForSku(request, scenario.trackedSku)).toBe(initialQuantity - ORDERED_QUANTITY);
    });
  }
});
