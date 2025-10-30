/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import InvoiceOverviewPage from "Pages/backend/InvoiceOverviewPage";
import InvoicePage from "Pages/backend/InvoicePage";
import CreditMemoPage from "Pages/backend/CreditMemoPage";
import PlaceOrderComposite from "CompositeActions/PlaceOrderComposite";
import OrdersPage from "Pages/backend/OrdersPage";

const invoiceOverviewPage = new InvoiceOverviewPage(expect);
const invoicePage = new InvoicePage(expect);
const creditMemoPage = new CreditMemoPage(expect);
const placeOrderComposite = new PlaceOrderComposite();
const ordersPage = new OrdersPage();

test('[C3111] Can do a refund on an iDeal order', async ({page}) => {
  const incrementId = await placeOrderComposite.placeOrder(page);

  await ordersPage.openByIncrementId(page, incrementId);

  await invoiceOverviewPage.openFirstInvoice(page);

  await invoicePage.creditMemo(page);

  await creditMemoPage.refund(page);
});
