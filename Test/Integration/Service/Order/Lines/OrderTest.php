<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Mollie\Payment\Service\Order\Lines\Order as Subject;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderTest extends IntegrationTestCase
{
    public function testGetOrderLinesProductCalculation()
    {
        $this->loadFixture('Magento/Sales/order_item_list.php');

        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');

        foreach ($order->getItems() as $item) {
            $item->setIsVirtual(false);
        }

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

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
        $order->setBaseCurrencyCode('EUR');

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

        // We expect 3 rows: the first product comes from the order creation, the second is our custom product.
        // The 3rd row is the shipping fee.
        $this->assertCount(3, $result);

        $row = $result[2]; // Shipping fee
        $this->assertEquals('shipping_fee', $row['type']);
        $this->assertEquals(12.1, $row['unitPrice']['value']);
        $this->assertEquals(12.1, $row['totalAmount']['value']);
        $this->assertEquals(2.1, $row['vatAmount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_bundle.php
     */
    public function testGeneratesAnEmptyLineForTheMainBundleProduct()
    {
        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

        $lines = array_filter($result, function ($line) {
            return $line['name'] == 'bundle1';
        });

        $line = array_shift($lines);
        $this->assertEquals(0, $line['unitPrice']['value']);
        $this->assertEquals(0, $line['totalAmount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_bundle.php
     * @magentoConfigFixture current_store payment/mollie_general/currency 0
     */
    public function testTheSimpleProductsHaveAPriceAvailable()
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Does not work on CI for some reason');
        }

        $order = $this->loadOrderById('100000001');
        $order->setOrderCurrencyCode('EUR');

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

        $lines = array_filter($result, function ($line) {
            return $line['sku'] == 'bundle_simple_1';
        });

        $line = array_shift($lines);
        $this->assertEquals(9.2, $line['unitPrice']['value']);
        $this->assertEquals(92, $line['totalAmount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_bundle.php
     */
    public function testTheSkuGetsTruncated()
    {
        $sku = 'somereallyreallylongskunumberthatgetsautogeneratedandexceedthemaximumof64characters';
        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');
        $items = $order->getItems();
        array_shift($items)->setSku($sku);

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

        $line = array_shift($result);
        $this->assertEquals(substr($sku, 0, 64), $line['sku']);
    }

    /**
     * @dataProvider adjustmentsDataProvider
     * @magentoDataFixture Magento/Sales/_files/order_with_bundle.php
     */
    public function testAddsAdjustmentsWhenTheTotalIsOff($adjustment)
    {
        $order = $this->loadOrderById('100000001');
        $order->setOrderCurrencyCode('EUR');
        $order->setBaseCurrencyCode('EUR');
        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $adjustment);

        foreach ($order->getItems() as $item) {
            $item->setBaseRowTotal(50);
        }

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);
        $lastLine = end($result);

        $this->assertEquals('discount', $lastLine['type']);
        $this->assertEquals($adjustment, $lastLine['totalAmount']['value']);

        $total = 0;
        foreach ($result as $orderLine) {
            $total += $orderLine['totalAmount']['value'];
        }

        $this->assertEquals($order->getBaseGrandTotal(), $total);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_bundle.php
     */
    public function testDoesNotAddTheAdjustmentsWhenTheTotalIsMoreThanFiveCents()
    {
        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setBaseGrandTotal($order->getBaseGrandTotal() + 0.06);

        foreach ($order->getItems() as $item) {
            $item->setBaseRowTotal(50);
        }

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);
        $lastLine = end($result);

        $this->assertNotEquals('discount', $lastLine['type']);
    }

    public function testAddsTheItemIdToTheMetadata(): void
    {
        $this->loadFixture('Magento/Sales/order_item_list.php');

        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

        foreach ($result as $line) {
            if (!in_array($line['type'], ['physical', 'digital'])) {
                continue;
            }

            $this->assertArrayHasKey('metadata', $line);
            $this->assertArrayHasKey('item_id', $line['metadata']);
        }
    }

    public function testSupportsProductsWithVowelMutations(): void
    {
        $this->loadFixture('Magento/Sales/order_item_list.php');

        $order = $this->loadOrderById('100000001');
        $order->setBaseCurrencyCode('EUR');

        $products = $order->getItems();
        $product = array_shift($products);
        $product->setName('Demö Produçt');

        /** @var Subject $instance */
        $instance = $this->objectManager->get(Subject::class);

        $result = $instance->get($order);

        $result = array_filter($result, function ($line) {
            return $line['name'] == 'Demö Produçt';
        });

        $this->assertCount(1, $result);
    }

    public function adjustmentsDataProvider(): array
    {
        return [
            [-0.05],
            [-0.04],
            [-0.03],
            [-0.02],
            [-0.01],
            [0.01],
            [0.02],
            [0.03],
            [0.04],
            [0.05],
        ];
    }
}
