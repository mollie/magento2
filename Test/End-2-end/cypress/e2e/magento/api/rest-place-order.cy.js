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

describe('Check that the headless REST endpoints work as expected', () => {
  it('C1988313: Validate that an order can be placed through REST', () => {
    cy.visit('opt/mollie-pwa-rest.html');

    cy.get('[data-key="start-checkout-process"]').click();

    cy.get('[data-key="mollie_methods_ideal"]').click();

    cy.get('[data-key="place-order-action"]').click();

    cy.get('[data-key="order-id"]').then((element) => {
      cy.wrap(element.text()).as('order-id');
    });

    cookies.disableSameSiteCookieRestrictions();

    cy.get('[data-key="redirect-url"]').then((element) => {
      cy.visit(element.attr('href'));
    });

    mollieHostedPaymentPage.selectFirstIssuer();
    mollieHostedPaymentPage.selectStatus('paid');

    checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

    cy.backendLogin(false);

    cy.get('@order-id').then((orderId) => {
      ordersPage.openOrderById(orderId);
    });

    ordersPage.assertOrderStatusIs('Processing');
  });

  it('C4245493: Validate that an point of sale order can be placed through REST', () => {
    cy.visit('opt/mollie-pwa-rest.html');

    cy.get('[data-key="start-checkout-process"]').click();

    cy.get('[data-key="mollie_methods_pointofsale"]', {timeout: 20_000}).click();

    cy.get('.terminal-list [type="radio"]').first().check();

    cy.get('[data-key="place-order-action"]').click();

    cy.get('[data-key="order-id"]').then((element) => {
      cy.wrap(element.text()).as('order-id');
    });

    cookies.disableSameSiteCookieRestrictions();

    cy.backendLogin(false);

    cy.get('@order-id').then((orderId) => {
        ordersPage.openOrderById(orderId);
    });

    ordersPage.assertOrderStatusIs('Pending Payment');

    findSelectorOrReload('.change-payment-status span').then((element) => {
      const dataUrl = element.attr('data-url');
      cy.visit(dataUrl);
    });

    mollieHostedPaymentPage.selectStatus('paid');

    cy.get('@order-id').then((orderId) => {
      ordersPage.openOrderById(orderId);
    });

    ordersPage.assertOrderStatusIs('Processing');
  });
})

function findSelectorOrReload(selector) {
  return cy.get('body').then($body => {
    if ($body.find(selector).length > 0) {
      return cy.get(selector);
    }

    cy.reload();
    return findSelectorOrReload(selector);
  });
}
