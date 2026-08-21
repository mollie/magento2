<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model;

use Exception;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Model\OrderLinesFactory;
use Mollie\Payment\Model\ResourceModel\OrderLines\Collection;
use Mollie\Payment\Service\Order\Lines\Order as OrderOrderLines;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderLinesTest extends IntegrationTestCase
{
    public function testGetCreditmemoOrderLines(): void
    {
        $creditmemo = $this->objectManager->get(CreditmemoInterface::class);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getCreditmemoOrderLines($creditmemo, false);

        $this->assertCount(0, $result['lines']);
    }

    public function testGetCreditmemoOrderLinesIncludesTheStoreCredit(): void
    {
        $orderLine = $this->objectManager->get(OrderLinesFactory::class)->create();
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

    public function testCreditmemoUsesTheDiscount(): void
    {
        /** @var OrderLines $orderLine */
        $orderLine = $this->objectManager->get(OrderLinesFactory::class)->create();
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

    public function testGetInvoiceOrderLines(): void
    {
        $orderLine = $this->objectManager->get(OrderLinesFactory::class)->create();
        $orderLine->setItemId(999);
        $orderLine->setLineId('odl_abc123');
        $orderLine->save();

        /** @var InvoiceItemInterface $invoiceItem */
        $invoiceItem = $this->objectManager->create(InvoiceItemInterface::class);
        $invoiceItem->setOrderItemId(999);
        $invoiceItem->setQty(2);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');

        /** @var InvoiceInterface $invoice */
        $invoice = $this->objectManager->create(InvoiceInterface::class);
        $invoice->setOrder($order);
        $invoice->setBaseShippingAmount(0);
        $invoice->setItems([$invoiceItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getInvoiceOrderLines($invoice);

        $this->assertCount(1, $result['lines']);

        $line = $result['lines'][0];
        $this->assertEquals('odl_abc123', $line['id']);
        $this->assertEquals(2, $line['quantity']);
        $this->assertArrayNotHasKey('amount', $line);
    }

    public function testGetInvoiceOrderLinesSkipsItemsWithoutAMollieLine(): void
    {
        /** @var InvoiceItemInterface $invoiceItem */
        $invoiceItem = $this->objectManager->create(InvoiceItemInterface::class);
        $invoiceItem->setOrderItemId(123456);
        $invoiceItem->setQty(1);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var InvoiceInterface $invoice */
        $invoice = $this->objectManager->create(InvoiceInterface::class);
        $invoice->setOrder($order);
        $invoice->setBaseShippingAmount(0);
        $invoice->setItems([$invoiceItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getInvoiceOrderLines($invoice);

        $this->assertCount(0, $result['lines']);
    }

    public function testInvoiceUsesTheDiscount(): void
    {
        $orderLine = $this->objectManager->get(OrderLinesFactory::class)->create();
        $orderLine->setItemId(999);
        $orderLine->setLineId('odl_abc123');
        $orderLine->save();

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setId(999);
        $orderItem->setBaseRowTotal(45); // 45 - 21% tax
        $orderItem->setBaseTaxAmount(7.56);
        $orderItem->setBaseDiscountAmount(9);
        $orderItem->setBaseDiscountTaxCompensationAmount(0);
        $orderItem->setQtyOrdered(1);

        /** @var InvoiceItemInterface $invoiceItem */
        $invoiceItem = $this->objectManager->create(InvoiceItemInterface::class);
        $invoiceItem->setQty(1);
        $invoiceItem->setOrderItem($orderItem);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');
        $order->setDiscountAmount(-9);

        /** @var InvoiceInterface $invoice */
        $invoice = $this->objectManager->create(InvoiceInterface::class);
        $invoice->setOrder($order);
        $invoice->setBaseShippingAmount(0);
        $invoice->setItems([$invoiceItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getInvoiceOrderLines($invoice);

        $this->assertCount(1, $result['lines']);

        $line = $result['lines'][0];
        $this->assertEquals('EUR', $line['amount']['currency']);
        $this->assertEquals(45 - 9 + 7.56, $line['amount']['value']);
        $this->assertEquals(1, $line['quantity']);
    }

    public function testGetInvoiceOrderLinesHandlesDatabaseStringsAndSkipsZeroQtyItems(): void
    {
        $invoicedLine = $this->objectManager->get(OrderLinesFactory::class)->create();
        $invoicedLine->setItemId(999);
        $invoicedLine->setLineId('odl_invoiced');
        $invoicedLine->save();

        $skippedLine = $this->objectManager->get(OrderLinesFactory::class)->create();
        $skippedLine->setItemId(1000);
        $skippedLine->setLineId('odl_skipped');
        $skippedLine->save();

        /** @var InvoiceItemInterface $invoicedItem */
        $invoicedItem = $this->objectManager->create(InvoiceItemInterface::class);
        $invoicedItem->setOrderItemId(999);
        $invoicedItem->setQty('1.0000');

        /** @var InvoiceItemInterface $notInvoicedItem */
        $notInvoicedItem = $this->objectManager->create(InvoiceItemInterface::class);
        $notInvoicedItem->setOrderItemId(1000);
        $notInvoicedItem->setQty('0.0000');

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');
        $order->setDiscountAmount('0.0000');

        /** @var InvoiceInterface $invoice */
        $invoice = $this->objectManager->create(InvoiceInterface::class);
        $invoice->setOrder($order);
        $invoice->setBaseShippingAmount(0);
        $invoice->setItems([$invoicedItem, $notInvoicedItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getInvoiceOrderLines($invoice);

        $this->assertCount(1, $result['lines']);
        $this->assertEquals('odl_invoiced', $result['lines'][0]['id']);
        $this->assertEquals(1, $result['lines'][0]['quantity']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testGetShipmentOrderLines(): void
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
            $orderLine = $this->objectManager->get(OrderLines::class);
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

    public function testGetShipmentOrderLinesHandlesDatabaseStrings(): void
    {
        $shippedLine = $this->objectManager->get(OrderLinesFactory::class)->create();
        $shippedLine->setItemId(999);
        $shippedLine->setLineId('odl_shipped');
        $shippedLine->save();

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setItemId(999);
        $orderItem->setBaseRowTotal('45.0000'); // 45 - 21% tax
        $orderItem->setBaseTaxAmount('7.5600');
        $orderItem->setBaseDiscountAmount('9.0000');
        $orderItem->setBaseDiscountTaxCompensationAmount('0.0000');
        $orderItem->setQtyOrdered('1.0000');

        /** @var ShipmentItemInterface $shipmentItem */
        $shipmentItem = $this->objectManager->create(ShipmentItemInterface::class);
        $shipmentItem->setQty('1.0000');
        $shipmentItem->setOrderItem($orderItem);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(999999);
        $order->setBaseCurrencyCode('EUR');
        $order->setDiscountAmount('-9.0000');

        /** @var ShipmentInterface $shipment */
        $shipment = $this->objectManager->create(ShipmentInterface::class);
        $shipment->setOrder($order);
        $shipment->setItems([$shipmentItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getShipmentOrderLines($shipment);

        $this->assertCount(1, $result['lines']);
        $this->assertEquals('odl_shipped', $result['lines'][0]['id']);
        $this->assertEquals('EUR', $result['lines'][0]['amount']['currency']);
        $this->assertEquals(45 - 9 + 7.56, $result['lines'][0]['amount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testGetShipmentOrderLinesAddsAnAmountWhenTheOrderHasAnDiscount(): void
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
            $orderLine = $this->objectManager->create(OrderLines::class);
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

    public function tearDownWithoutVoid(): void
    {
        $collection = $this->objectManager->create(Collection::class);

        foreach ($collection as $creditmemo) {
            $creditmemo->delete();
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @throws Exception
     */
    public function testHandlesNegativeDiscountAmounts(): void
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

        /** @var OrderOrderLines $instance */
        $instance = $this->objectManager->get(OrderOrderLines::class);

        $result = $instance->get($order);

        $productLine = $result[0];
        $this->assertEquals(0.01, $productLine['discountAmount']['value']);
        $this->assertEquals(12.94, $productLine['totalAmount']['value']);
        $this->assertEquals(12.95, $productLine['unitPrice']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @throws Exception
     */
    public function testHandlesBundleProductWithStringRowTotalInclTax(): void
    {
        // Bundle order items store their row total as a decimal string ("24.0000"); under
        // declare(strict_types=1) that string used to break the ?float return of
        // getTotalAmountOrderItem() before it is called for the line, throwing a TypeError.
        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setProductId(1);
        $orderItem->setProductType('bundle');
        $orderItem->setQtyOrdered(1);
        $orderItem->setTaxPercent(0);
        $orderItem->setDiscountAmount(0);
        $orderItem->setDiscountTaxCompensationAmount(0);
        $orderItem->setBaseDiscountAmount(0);
        $orderItem->setBaseDiscountTaxCompensationAmount(0);
        $orderItem->setRowTotalInclTax('24.0000');
        $orderItem->setBaseRowTotalInclTax('24.0000');

        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setItems([$orderItem]);

        /** @var OrderOrderLines $instance */
        $instance = $this->objectManager->get(OrderOrderLines::class);

        $result = $instance->get($order);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('totalAmount', $result[0]);
    }
}
