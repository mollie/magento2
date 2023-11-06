/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";

const visitCheckoutPaymentCompositeAction = new VisitCheckoutPaymentCompositeAction();
const checkoutPaymentsPage = new CheckoutPaymentPage();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();

export default class PlaceOrderComposite {
    placeOrder() {
        visitCheckoutPaymentCompositeAction.visit();

        checkoutPaymentsPage.selectPaymentMethod('iDeal');
        checkoutPaymentsPage.selectFirstAvailableIssuer();
        checkoutPaymentsPage.placeOrder();

        mollieHostedPaymentPage.selectStatus('paid');

        checkoutSuccessPage.assertThatOrderSuccessPageIsShown();
    }
}
