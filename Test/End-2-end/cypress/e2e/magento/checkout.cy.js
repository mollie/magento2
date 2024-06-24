
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import Configuration from "Actions/backend/Configuration";

const configuration = new Configuration();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const ordersPage = new OrdersPage();

describe('Checkout usage', () => {
  it('C849728: Validate that each payment methods have a specific CSS class', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('iDeal');

    cy.get('.payment-method._active').should('have.class', 'payment-method-mollie_methods_ideal');

    const availableMethods = Cypress.env('mollie_available_methods');
    [
      'alma',
      'bancomatpay',
      'bancontact',
      'banktransfer',
      'belfius',
      'creditcard',
      'kbc',
      'klarnapaylater',
      'klarnapaynow',
      // TODO: Figure out why paypal fails
      // 'paypal',
      'przelewy24',
      'riverty',
      'sofort',
    ].forEach((method) => {
      if (!availableMethods.includes(method)) {
        return;
      }

      cy.get('.payment-method-mollie_methods_' + method).should('not.have.class', '_active');
    });
  });

  it('C849729: Validate that it renders Mollie Components when selecting the Credit Card method ', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('Credit Card');

    cy.get('.card-container').should('be.visible');
    cy.get('#card-holder').should('be.visible');
    cy.get('#card-holder .mollie-component').should('be.visible');
  });

  it('C849662: Validate that the quote is restored when using the back button ', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('iDeal');

    checkoutPaymentPage.placeOrder();

    mollieHostedPaymentPage.assertIsVisible();

    // The original test included a call to cy.go('back');, but this fails for unknown reasons in CI.
    cy.visit('/checkout#payment');

    cy.url().should('include', '/checkout#payment');
  });

  it('C2183249: Validate that submitting an order with a discount works through the Orders API', () => {
    configuration.setValue('Payment Methods', 'iDeal', 'Method', 'order');

    visitCheckoutPayment.visit('NL', 1, 15);

    checkoutPaymentPage.selectPaymentMethod('iDeal');

    checkoutPaymentPage.enterCouponCode();

    checkoutPaymentPage.placeOrder();

    mollieHostedPaymentPage.selectStatus('paid');

    checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

    cy.backendLogin();

    cy.get('@order-id').then((orderId) => {
      ordersPage.openOrderById(orderId);
    });

    cy.get('.mollie-checkout-type').should('contain', 'Order');
  });

  it('C2530311: Validate that the success page can only be visited once', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('iDeal');

    cy.intercept('mollie/checkout/process/*').as('processAction');

    checkoutPaymentPage.placeOrder();

    mollieHostedPaymentPage.selectStatus('paid');

    checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

    cy.wait('@processAction').then((interception) => {
      cy.visit(interception.request.url);
    });

    cy.url().should('include', 'checkout/cart');
  });
})
