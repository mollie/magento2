import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutShippingPage = new CheckoutShippingPage();

export default class VisitCheckoutPaymentCompositeAction {
  visit(fixture = 'NL', quantity = 1, productId = Cypress.env('defaultProductId')) {
    productPage.openProduct(productId);

    productPage.addSimpleProductToCart(quantity);

    checkoutPage.visit();

    this.fillAddress(fixture);

    checkoutShippingPage.selectFirstAvailableShippingMethod();
    checkoutPage.continue();
  }

  visitAsCustomer(fixture = 'NL', quantity = 1) {
    productPage.openProduct(Cypress.env('defaultProductId'));

    productPage.addSimpleProductToCart(quantity);

    checkoutPage.visit();

    this.fillAddress(fixture, true);

    checkoutShippingPage.selectFirstAvailableShippingMethod();
    checkoutPage.continue();
  }

  fillAddress(fixture, asCustomer = false) {
      if (asCustomer) {
          checkoutShippingPage.skipUsername();
      }

    if (fixture === 'DE') {
      checkoutShippingPage.fillGermanShippingAddress();
      return;
    }

    if (fixture === 'NL') {
      checkoutShippingPage.fillDutchShippingAddress();
      return;
    }

    checkoutShippingPage.fillShippingAddressUsingFixture(fixture);
  }

  changeCurrencyTo(currency) {
    cy.visit('/');

    cy.get('.greet.welcome').should('be.visible');

    cy.get('#switcher-currency-trigger span').click();
    cy.get('.switcher-dropdown').contains(currency).click();
  }

  changeStoreViewTo(name) {
    cy.visit('/');

    cy.get('.greet.welcome').should('be.visible');

    cy.get('.switcher-trigger').then(($el) => {
        if ($el.text().includes('Default Store View')) {
            cy.get('#switcher-language-trigger .view-default').click();

            cy.get('.switcher-dropdown').contains(name).click();
        }
    });
  }
}
