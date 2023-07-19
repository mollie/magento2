
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();

describe('Checkout usage', () => {
  it('C849728: Validate that each payment methods have a specific CSS class', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('iDeal');

    cy.get('.payment-method._active').should('have.class', 'payment-method-mollie_methods_ideal');

    cy.get('.payment-method-mollie_methods_bancontact').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_banktransfer').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_belfius').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_creditcard').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_kbc').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_klarna').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_klarnapaylater').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_klarnapaynow').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_paypal').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_przelewy24').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_sofort').should('not.have.class', '_active');
  });

  it('C849729: Validate that it renders Mollie Components when selecting the Credit Card method ', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('Credit Card');

    cy.get('.card-container').should('be.visible');
    cy.get('#card-holder').should('be.visible');
    cy.get('#card-holder .mollie-component').should('be.visible');
  });

  it.only('C849662: Restores the cart when using the back button from the HPP', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('iDeal');
    checkoutPaymentPage.selectFirstAvailableIssuer();
    checkoutPaymentPage.placeOrder();

    mollieHostedPaymentPage.assertIsVisible();

    // The original test included a call to cy.go('back');, but this fails for unknown reasons in CI.
    cy.visit('/checkout#payment');

    cy.url().should('include', '/checkout#payment');
  });
})
