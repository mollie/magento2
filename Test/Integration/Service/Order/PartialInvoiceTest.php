<?php

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Payment\Service\Order\PartialInvoice;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PartialInvoiceTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testDoesNotCreateInvoiceWhenWrongPaymentMethod()
    {
        $order = $this->loadOrder('100000001');
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments */
        $shipments = $order->getShipmentsCollection();

        /** @var PartialInvoice $instance */
        $instance = $this->objectManager->get(PartialInvoice::class);

        $shipment = $shipments->getFirstItem();
        $shipment->getOrder()->setMollieTransactionId('ord_abc123')->save();

        $this->assertNull($instance->createFromShipment($shipment));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/invoice_moment shipment
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testCreatesAnInvoice()
    {
        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();
        $payment->setMethod('mollie_methods_klarnapaylater');
        $payment->save();

        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments */
        $shipments = $order->getShipmentsCollection();

        /** @var PartialInvoice $instance */
        $instance = $this->objectManager->get(PartialInvoice::class);
        $result = $instance->createFromShipment($shipments->getFirstItem());

        $this->assertInstanceOf(InvoiceInterface::class, $result);
        $this->assertEquals($result->getOrderId(), $order->getEntityId());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/invoice_moment shipment
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testDoesNotCreateDuplicateInvoiceWhenOrderAlreadyHasInvoice()
    {
        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();
        $payment->setMethod('mollie_methods_klarnapaylater');
        $payment->save();

        // Simulate admin manually creating an invoice before shipment
        /** @var InvoiceService $invoiceService */
        $invoiceService = $this->objectManager->get(InvoiceService::class);
        $invoice = $invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $invoice->save();

        $this->assertCount(1, $order->getInvoiceCollection()->getItems());

        // Now createFromShipment should not create another invoice
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments */
        $shipments = $order->getShipmentsCollection();
        $shipment = $shipments->getFirstItem();

        /** @var PartialInvoice $instance */
        $instance = $this->objectManager->get(PartialInvoice::class);
        $result = $instance->createFromShipment($shipment);

        $this->assertNull($result);

        // Verify still only 1 invoice exists
        $order = $this->loadOrder('100000001');
        $this->assertCount(1, $order->getInvoiceCollection()->getItems());
    }
}
