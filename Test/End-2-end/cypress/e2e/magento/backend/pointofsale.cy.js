import OrdersCreatePage from "Pages/backend/OrdersCreatePage";
import Cookies from "Services/Cookies";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import OrdersPage from "Pages/backend/OrdersPage";

const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const ordersPage = new OrdersPage();
const ordersCreatePage = new OrdersCreatePage();
const cookies = new Cookies();

describe('Placing orders using Point Of Sale from the backend', () => {
  it.skip('Is possible to place an order using Point Of Sale that is being paid', () => {
    cy.backendLogin();

    ordersCreatePage.createNewOrderFor('Veronica Costello');

    ordersCreatePage.addFirstSimpleProduct();

    ordersCreatePage.selectShippingMethod('Fixed');

    cy.get('[for="p_method_mollie_methods_pointofsale"]').click().click();

    cy.get('.pointofsale-terminal-list label').should('have.length.gte', 1);
    cy.get('.pointofsale-terminal-list label').first().click();

    cookies.disableSameSiteCookieRestrictions();

    ordersCreatePage.submitOrder();

    cy.url().then(url => {
      cy.wrap(url).as('order-url');
    });

    cy.get('.change-payment-status .mollie-copy-url')
      .invoke('attr', 'data-url')
      .then(href => {
        cy.visit(href);
      });

    mollieHostedPaymentPage.selectStatus('paid');

    cy.get('@order-url').then((url) => {
      cy.visit(url);
    });

    ordersPage.assertOrderStatusIs('Processing');
  });
})
