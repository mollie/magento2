/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import InvoiceOverviewPage from "Pages/backend/InvoiceOverviewPage";
import InvoicePage from "Pages/backend/InvoicePage";
import CreditMemoPage from "Pages/backend/CreditMemoPage";
import PlaceOrderComposite from "CompositeActions/PlaceOrderComposite";

const invoiceOverviewPage = new InvoiceOverviewPage();
const invoicePage = new InvoicePage();
const creditMemoPage = new CreditMemoPage();
const placeOrderComposite = new PlaceOrderComposite();

describe('Check that refunds behave as excepted', () => {
    it('Can do a refund on an iDeal order', () => {
        placeOrderComposite.placeOrder();

        cy.get('@order-id').then(orderId => {
            invoiceOverviewPage.openByOrderId(orderId);
        });

        invoicePage.creditMemo();

        creditMemoPage.refund();
    });
});
