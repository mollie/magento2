/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect} from "@playwright/test";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";

const visitCheckoutPaymentCompositeAction = new VisitCheckoutPaymentCompositeAction();
const checkoutPaymentsPage = new CheckoutPaymentPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);

export default class PlaceOrderComposite {
  async placeOrder(page): Promise<string> {
    await visitCheckoutPaymentCompositeAction.visit(page);

    await checkoutPaymentsPage.selectPaymentMethod(page, 'iDeal');
    await checkoutPaymentsPage.placeOrder(page);

    await mollieHostedPaymentPage.selectFirstIssuer(page);
    await mollieHostedPaymentPage.selectStatus(page, 'paid');

    await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);

    return mollieHostedPaymentPage.incrementId;
  }
}
