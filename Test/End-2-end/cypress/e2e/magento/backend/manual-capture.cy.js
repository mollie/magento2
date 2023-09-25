import Configuration from 'Actions/backend/Configuration';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import ComponentsAction from "Actions/checkout/ComponentsAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import InvoicePage from "Pages/backend/InvoicePage";

const configuration = new Configuration();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const components = new ComponentsAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const ordersPage = new OrdersPage();
const invoicePage = new InvoicePage();

describe('Manual capture works as expected', () => {
    after(() => {
        cy.backendLogin(false);

        // Make sure to set this back to No to not influence other tests
        configuration.setValue('Advanced', 'Triggers & Languages', 'Manual Capture', 'No');
    });

    it('C1064183: Validate that with manual capture disabled the invoice is created when placing the order', () => {
        configuration.setValue('Advanced', 'Triggers & Languages', 'Manual Capture', 'No');

        visitCheckoutPayment.visit();

        checkoutPaymentPage.selectPaymentMethod('Credit Card');

        components.fillComponentsForm(
            'Mollie Tester',
            '3782 822463 10005',
            '1230',
            '1234'
        );

        checkoutPaymentPage.placeOrder();

        mollieHostedPaymentPage.selectStatus('paid');

        checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

        cy.backendLogin();

        cy.get('@order-id').then((orderId) => {
            ordersPage.openOrderById(orderId);
        });

        ordersPage.assertOrderStatusIs('Processing');

      cy.get('@order-id').then((orderId) => {
        ordersPage.assertOrderHasInvoice(orderId);
      });
    });

    it('C1064182: Validate that with manual capture enabled the invoice is not automatically created', () => {
        configuration.setValue('Advanced', 'Triggers & Languages', 'Manual Capture', 'Yes');

        visitCheckoutPayment.visit();

        checkoutPaymentPage.selectPaymentMethod('Credit Card');

        components.fillComponentsForm(
            'Mollie Tester',
            '3782 822463 10005',
            '1230',
            '1234'
        );

        checkoutPaymentPage.placeOrder();

        mollieHostedPaymentPage.selectStatus('authorized');

        checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

        cy.backendLogin();

        cy.get('@order-id').then((orderId) => {
            ordersPage.openOrderById(orderId);
        });

        ordersPage.assertOrderStatusIs('Processing');

        cy.get('@order-id').then((orderId) => {
            ordersPage.assertOrderHasNoInvoices(orderId);
        });
    });

    it('C1572711: Validate that with manual capture enabled the capture is done when the invoice is created', () => {
      configuration.setValue('Advanced', 'Triggers & Languages', 'Manual Capture', 'Yes');

      visitCheckoutPayment.visit();

      checkoutPaymentPage.selectPaymentMethod('Credit Card');

      components.fillComponentsForm(
        'Mollie Tester',
        '3782 822463 10005',
        '1230',
        '1234'
      );

      checkoutPaymentPage.placeOrder();

      mollieHostedPaymentPage.selectStatus('authorized');

      checkoutSuccessPage.assertThatOrderSuccessPageIsShown();

      cy.backendLogin();

      cy.get('@order-id').then((orderId) => {
        ordersPage.openOrderById(orderId);
      });

      ordersPage.invoice();
      invoicePage.invoice();

      // Give the webhook some time to process
      cy.wait(5000);
      cy.reload();

      cy.contains('Trying to capture').should('be.visible');
      cy.contains('Successfully captured an amount of').should('be.visible');
      cy.contains('Registered notification about captured amount of').should('be.visible');

      cy.get('@order-id').then((orderId) => {
        ordersPage.assertOrderHasInvoice(orderId);
      });
    });
});
