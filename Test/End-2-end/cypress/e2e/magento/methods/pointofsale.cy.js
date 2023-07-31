import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import Configuration from "Actions/backend/Configuration";
import MagentoRestApi from "Services/MagentoRestApi";

const configuration = new Configuration();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const magentoRestApi = new MagentoRestApi();

describe('Point of sale behaves as expected', () => {
  it('C1259056: Validate that Point Of Sale is not shown for a guest user', () => {
    visitCheckoutPayment.visit();

    cy.get('[value="mollie_methods_pointofsale"]').should('not.exist');
  });

  it('C1259057: Validate that Point Of Sale is shown when the customer is in the correct customer group', () => {
    const email = Date.now() + 'user@example.com';

    magentoRestApi.createCustomer(email);

    cy.backendLogin();

    configuration.setValue(
      'Payment Methods',
      'Point Of Sale',
      'Payment from Applicable Customer Groups',
      'Retailer'
    );

    cy.visit('customer/account/login/');

    cy.get('[name="login[username]"]').type(email);
    cy.get('[name="login[password]"]').type('Password1234');

    cy.get('.action.login.primary').click();

    cy.url().should('include', '/customer/account/');

    visitCheckoutPayment.visitAsCustomer();

    cy.get('[value="mollie_methods_pointofsale"]').should('exist');

    checkoutPaymentPage.selectPaymentMethod('Point Of Sale');

    cy.contains('Select Terminal').should('be.visible');
  })
});
