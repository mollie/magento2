/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import Cookies from "Services/Cookies";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";

const cookies = new Cookies();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const ordersPage = new OrdersPage();

describe('Check that the headless endpoints work as expected', () => {
  it('C1835263: Validate that an order can be placed through GraphQL ', () => {
    cy.visit('opt/mollie-pwa.html');

    cy.get('[data-key="start-checkout-process"]').click();

    cy.get('[data-key="mollie_methods_ideal"]').click();

    cy.get('[data-key="mollie_methods_ideal-issuer"]').first().click();

    cy.get('[data-key="place-order-action"]').click();

    cy.get('[data-key="increment-id"]').then((element) => {
      cy.wrap(element.text()).as('increment-id');
    });

    cookies.disableSameSiteCookieRestrictions();

    cy.get('[data-key="redirect-url"]').then((element) => {
      cy.visit(element.attr('href'));
    });

    mollieHostedPaymentPage.selectStatus('paid');

    checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

    cy.backendLogin(false);

    cy.get('@increment-id').then((incrementId) => {
      ordersPage.openByIncrementId(incrementId);
    });

    ordersPage.assertOrderStatusIs('Processing');
  });
})
