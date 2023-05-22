import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutShippingPage = new CheckoutShippingPage();

export default class VisitCheckoutPaymentCompositeAction {
  visit(country = 'NL') {
    productPage.openProduct(Cypress.env('defaultProductId'));
    productPage.addSimpleProductToCart();

    checkoutPage.visit();

    this.fillAddress(country);

    checkoutShippingPage.selectFirstAvailableShippingMethod();
    checkoutPage.continue();
  }

  fillAddress(country) {
    if (country === 'DE') {
      checkoutShippingPage.fillGermanShippingAddress();
      return;
    }

    checkoutShippingPage.fillDutchShippingAddress();
  }
}
