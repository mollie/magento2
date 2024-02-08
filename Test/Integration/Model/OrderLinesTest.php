<?php

namespace Mollie\Payment\Test\Integration\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderLinesTest extends IntegrationTestCase
{
    public function testGetCreditmemoOrderLines()
    {
        $creditmemo = $this->objectManager->get(CreditmemoInterface::class);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getCreditmemoOrderLines($creditmemo, false);

        $this->assertCount(0, $result['lines']);
    }

    public function testGetCreditmemoOrderLinesIncludesTheStoreCredit()
    {
        $orderLine = $this->objectManager->get(\Mollie\Payment\Model\OrderLinesFactory::class)->create();
        $orderLine->setOrderId(999);
        $orderLine->setLineId('ord_abc123');
        $orderLine->setType('store_credit');
        $orderLine->save();

        $creditmemo = $this->objectManager->get(CreditmemoInterface::class);
        $creditmemo->setOrderId(999);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getCreditmemoOrderLines($creditmemo, false);

        $this->assertCount(1, $result['lines']);

        $line = $result['lines'][0];
        $this->assertEquals('ord_abc123', $line['id']);
        $this->assertEquals(1, $line['quantity']);
    }

    public function testCreditmemoUsesTheDiscount()
    {
        /** @var OrderLines $orderLine */
        $orderLine = $this->objectManager->get(\Mollie\Payment\Model\OrderLinesFactory::class)->create();
        $orderLine->setItemId(999);
        $orderLine->setLineId('ord_abc123');
        $orderLine->save();

        /** @var CreditmemoItemInterface $creditmemoItem */
        $creditmemoItem = $this->objectManager->create(CreditmemoItemInterface::class);
        $creditmemoItem->setBaseRowTotal(45); // 45 - 21% tax
        $creditmemoItem->setBaseRowTotalInclTax(45 * 1.21);
        $creditmemoItem->setBaseDiscountAmount(9);
        $creditmemoItem->setBaseTaxAmount(7.56); // 21% tax
        $creditmemoItem->setQty(1);
        $creditmemoItem->setOrderItemId(999);

        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->objectManager->get(CreditmemoInterface::class);
        $creditmemo->setBaseCurrencyCode('EUR');
        $creditmemo->setOrderId(999);
        $creditmemo->setItems([$creditmemoItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getCreditmemoOrderLines($creditmemo, false);

        $this->assertCount(1, $result['lines']);

        $line = $result['lines'][0];
        $this->assertEquals(45 - 9 + 7.56, $line['amount']['value']);
        $this->assertEquals(1, $line['quantity']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testGetShipmentOrderLines()
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Does not work on CI for some reason');
        }

        $order = $this->loadOrder('100000001');

        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments */
        $shipments = $order->getShipmentsCollection();

        /** @var ShipmentInterface $shipment */
        $shipment = $shipments->getFirstItem();

        foreach ($shipment->getItems() as $item) {
            /** @var OrderLines $orderLine */
            $orderLine = $this->objectManager->get(\Mollie\Payment\Model\OrderLines::class);
            $orderLine->setItemId($item->getOrderItemId());
            $orderLine->setLineId('ord_abc123');
            $orderLine->save();
        }

        /** @var OrderLines $instance */
        $instance = $this->objectManager->create(OrderLines::class);

        $result = $instance->getShipmentOrderLines($shipment);

        $this->assertCount(1, $result['lines']);
        $this->assertEquals('ord_abc123', $result['lines'][0]['id']);
        $this->assertEquals(2, $result['lines'][0]['quantity']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testGetShipmentOrderLinesAddsAnAmountWhenTheOrderHasAnDiscount()
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Does not work on CI for some reason');
        }

        $order = $this->loadOrder('100000001');
        $order->setDiscountAmount(10);
        $order->setBaseCurrencyCode('EUR');
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipments */
        $shipments = $order->getShipmentsCollection();

        /** @var ShipmentInterface $shipment */
        $shipment = $shipments->getFirstItem();

        foreach ($shipment->getItems() as $item) {
            /** @var OrderLines $orderLine */
            $orderLine = $this->objectManager->create(\Mollie\Payment\Model\OrderLines::class);
            $orderLine->setItemId($item->getOrderItemId());
            $orderLine->setLineId('ord_abc123');
            $orderLine->save();

            /** @var OrderItemInterface $orderItem */
            $orderItem = $item->getOrderItem();
            $orderItem->setBaseRowTotal(100);
            $orderItem->setBaseTaxAmount(21);
            $orderItem->setBaseRowTotalInclTax(121);
            $orderItem->setBaseDiscountAmount(30);
            $orderItem->setQtyOrdered(10);
        }

        /** @var OrderLines $instance */
        $instance = $this->objectManager->create(OrderLines::class);
        $result = $instance->getShipmentOrderLines($shipment);

        $this->assertCount(1, $result['lines']);
        $this->assertEquals('ord_abc123', $result['lines'][0]['id']);
        $this->assertEquals(2, $result['lines'][0]['quantity']);
        $this->assertEquals('EUR', $result['lines'][0]['amount']['currency']);

        // 100 euro subtotal
        // 21 euro tax
        // 30 discount
        // 70 grand total
        // 10 items = 10 euro each
        // 2 items ordered
        // ((100 + 21 - 30) / 10) * 2 = 14
        $this->assertEquals(18.2, $result['lines'][0]['amount']['value']);
    }

    public function tearDownWithoutVoid()
    {
        $collection = $this->objectManager->create(\Mollie\Payment\Model\ResourceModel\OrderLines\Collection::class);

        foreach ($collection as $creditmemo) {
            $creditmemo->delete();
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @throws \Exception
     */
    public function testHandlesNegativeDiscountAmounts()
    {
        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setProductId(1);
        $orderItem->setQtyOrdered(1);
        $orderItem->setBaseRowTotal(12.95);
        $orderItem->setBaseDiscountAmount(0);
        $orderItem->setBaseDiscountTaxCompensationAmount(-0.01);

        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setItems([$orderItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);

        $result = $instance->getOrderLines($order);

        $productLine = $result[0];
        $this->assertEquals(0.01, $productLine['discountAmount']['value']);
        $this->assertEquals(12.94, $productLine['totalAmount']['value']);
        $this->assertEquals(12.95, $productLine['unitPrice']['value']);
    }
}
