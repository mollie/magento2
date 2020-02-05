<?php

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
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

        $this->assertNull($instance->createFromShipment($shipments->getFirstItem()));
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
}
