import Configuration from 'Actions/backend/Configuration';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import ComponentsAction from "Actions/checkout/ComponentsAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import ShipmentPage from "Pages/backend/ShipmentPage";

const configuration = new Configuration();
const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const components = new ComponentsAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage();
const checkoutSuccessPage = new CheckoutSuccessPage();
const ordersPage = new OrdersPage();
const shipmentPage = new ShipmentPage();

describe('Manual capture works as expected', () => {
    after(() => {
        cy.backendLogin();

        // Make sure to set this back to No to not influence other tests
        configuration.setValue('Advanced', 'Triggers & Languages', 'Manual Capture', 'No');
    });

    it('C1064183: Validate that with manual capture enabled the invoice is created when placing the order', () => {
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

    it('C1064182: Validate that with manual capture enabled the invoice is created when a shipment is created', () => {
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

        ordersPage.ship();

        shipmentPage.ship();

        cy.get('@order-id').then((orderId) => {
            ordersPage.assertOrderHasInvoice(orderId);
        });
    });
});
