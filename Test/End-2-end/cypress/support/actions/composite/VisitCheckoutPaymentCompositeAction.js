import ProductPage from "Pages/frontend/ProductPage";
import CheckoutPage from "Pages/frontend/CheckoutPage";
import CheckoutShippingPage from "Pages/frontend/CheckoutShippingPage";

const productPage = new ProductPage();
const checkoutPage = new CheckoutPage();
const checkoutShippingPage = new CheckoutShippingPage();

export default class VisitCheckoutPaymentCompositeAction {
  visit() {
    productPage.openProduct(3);
    productPage.addSimpleProductToCart();

    checkoutPage.visit();

    checkoutShippingPage.fillDutchShippingAddress();

    checkoutShippingPage.selectFirstAvailableShippingMethod();
    checkoutPage.continue();
  }
}
