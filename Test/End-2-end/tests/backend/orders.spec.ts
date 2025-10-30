/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import PlaceOrderComposite from "CompositeActions/PlaceOrderComposite";
import OrdersPage from "Pages/backend/OrdersPage";
import Configuration from "Actions/backend/Configuration";

const placeOrderComposite = new PlaceOrderComposite();
const ordersPage = new OrdersPage();
const configuration = new Configuration(expect);

test('Does not create invoice when invoice creation is disabled', async ({page}) => {
    await page.goto('/admin/');

    await configuration.setValue(page, 'Order Management', 'Advanced', 'Create invoice on successful payment', 'No');

    try {
        const incrementId = await placeOrderComposite.placeOrder(page);

        await ordersPage.openByIncrementId(page, incrementId);

        await ordersPage.assertOrderStatusIs(page, 'Processing');

        await ordersPage.openTab(page, 'Invoices');

        await expect(page.locator('#sales_order_view_tabs_order_invoices_content'))
            .toContainText('We couldn\'t find any records.');
    } finally {
        await configuration.setValue(page, 'Order Management', 'Advanced', 'Create invoice on successful payment', 'Yes');
    }
})
