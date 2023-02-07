import Cookies from "Services/Cookies";
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";

const cookies = new Cookies();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();

describe('Check the checkout', () => {
  it('Should have a payment method specific class', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('iDeal');

    cy.get('.payment-method._active').should('have.class', 'payment-method-mollie_methods_ideal');

    cy.get('.payment-method-mollie_methods_bancontact').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_banktransfer').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_belfius').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_creditcard').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_kbc').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_klarnapaylater').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_klarnapaynow').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_paypal').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_przelewy24').should('not.have.class', '_active');
    cy.get('.payment-method-mollie_methods_sofort').should('not.have.class', '_active');
  });

  it('Should render Mollie Components when selecting Credit Card', () => {
    visitCheckoutPayment.visit();

    checkoutPaymentPage.selectPaymentMethod('Credit Card');

    cy.get('.card-container').should('be.visible');
    cy.get('#card-holder').should('be.visible');
    cy.get('#card-holder .mollie-component').should('be.visible');
  });
})
