import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutShippingPage = new CheckoutShippingPage();

export default class VisitCheckoutPaymentCompositeAction {
  visit(fixture = 'NL') {
    productPage.openProduct(Cypress.env('defaultProductId'));

    productPage.addSimpleProductToCart();

    checkoutPage.visit();

    this.fillAddress(fixture);

    checkoutShippingPage.selectFirstAvailableShippingMethod();
    checkoutPage.continue();
  }

  fillAddress(fixture) {
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
}
