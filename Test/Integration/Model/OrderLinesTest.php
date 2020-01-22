<?php

namespace Mollie\Payment\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderLinesTest extends IntegrationTestCase
{
    public function testGetOrderLinesProductCalculation()
    {
        $this->loadFixture('Magento/Sales/order_item_list.php');

        $order = $this->loadOrderById('100000001');

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);

        $result = $instance->getOrderLines($order);

        // We expect 3 rows: the first product comes from the order creation, the second is our custom product.
        // The 3rd row is the shipping fee.
        $this->assertCount(3, $result);

        $row = $result[1]; // Custom created item
        $this->assertEquals(2, $row['quantity']);
        $this->assertEquals('physical', $row['type']);
        $this->assertEquals(100, $row['unitPrice']['value']);
        $this->assertEquals(200, $row['totalAmount']['value']);
        $this->assertEquals(34.71, $row['vatAmount']['value']);
    }

    public function testGetOrderLinesShippingFeeCalculation()
    {
        $this->loadFixture('Magento/Sales/order_item_list.php');

        $order = $this->loadOrderById('100000001');
        $order->setShippingAmount(10);
        $order->setBaseShippingAmount(10);
        $order->setShippingTaxAmount(2.1);
        $order->setBaseShippingTaxAmount(2.1);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);

        $result = $instance->getOrderLines($order);

        // We expect 3 rows: the first product comes from the order creation, the second is our custom product.
        // The 3rd row is the shipping fee.
        $this->assertCount(3, $result);

        $row = $result[2]; // Shipping fee
        $this->assertEquals('shipping_fee', $row['type']);
        $this->assertEquals(12.1, $row['unitPrice']['value']);
        $this->assertEquals(12.1, $row['totalAmount']['value']);
        $this->assertEquals(2.1, $row['vatAmount']['value']);
    }

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
        $this->rollbackCreditmemos();

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
        $creditmemoItem->setBaseRowTotalInclTax(45);
        $creditmemoItem->setBaseDiscountAmount(9);
        $creditmemoItem->setQty(1);
        $creditmemoItem->setOrderItemId(999);

        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->objectManager->get(CreditmemoInterface::class);
        $creditmemo->setOrderId(999);
        $creditmemo->setItems([$creditmemoItem]);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getCreditmemoOrderLines($creditmemo, false);

        $this->assertCount(1, $result['lines']);

        $line = $result['lines'][0];
        $this->assertEquals('36', $line['amount']['value']);
        $this->assertEquals(1, $line['quantity']);
    }

    public function testIncludesTheOrderDiscount()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setDiscountAmount(10);
        $order->setBaseDiscountAmount(10);

        /** @var OrderLines $instance */
        $instance = $this->objectManager->get(OrderLines::class);
        $result = $instance->getOrderLines($order);

        $discount = array_filter($result, function ($line) {
            return $line['type'] == 'discount';
        });

        $this->assertCount(1, $discount);

        $discount = array_shift($discount);
        $this->assertNotNull($discount);
        $this->assertEquals(10, $discount['unitPrice']['value']);
    }

    private function rollbackCreditmemos()
    {
        $collection = $this->objectManager->get(\Mollie\Payment\Model\ResourceModel\OrderLines\Collection::class);

        foreach ($collection as $creditmemo) {
            $creditmemo->delete();
        }
    }
}
