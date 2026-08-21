<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Processor;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Service\Order\Lines\Processor\BundleWithoutDynamicPricing;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BundleWithoutDynamicPricingTest extends IntegrationTestCase
{
    public function testSetsTheCorrectVatAmount(): void
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Does not work on CI for several reasons');
        }

        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options.php');

        $order = $this->loadOrderById('100000001');

        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $bundleItem = $this->getBundleItem($order);
        $bundleItem->setDiscountAmount(10);
        $bundleItem->setBaseDiscountAmount(10);
        $bundleItem->setTaxPercent(21);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'EUR',
            ],
        ];

        $result = $instance->process($orderLine, $order, $bundleItem);

        // 100 - 10 = 90, 21% VAT = 15.62
        $this->assertEquals(15.62, $result['vatAmount']['value']);

        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options_rollback.php');
    }

    public function testUsesTheQuantity(): void
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Does not work on CI for several reasons');
        }

        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options.php');

        $order = $this->loadOrderById('100000001');

        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $bundleItem = $this->getBundleItem($order);
        $bundleItem->setQtyOrdered(2);
        $bundleItem->setDiscountAmount(10);
        $bundleItem->setBaseDiscountAmount(10);
        $bundleItem->setTaxPercent(21);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'EUR',
            ],
        ];

        $result = $instance->process($orderLine, $order, $bundleItem);

        // 100 - 10 = 90, 21% VAT = 15.62
        $this->assertEquals(15.62, $result['vatAmount']['value']);
        $this->assertEquals(90, $result['totalAmount']['value']);

        $this->loadMagentoFixture('Magento/Bundle/_files/order_item_with_bundle_and_options_rollback.php');
    }

    public function testReturnsTheOrderLineUnchangedWhenTheDatabaseReportsNoDiscount(): void
    {
        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'USD',
            ],
        ];

        $result = $instance->process(
            $orderLine,
            $this->createOrder(),
            $this->createFixedPriceBundleItem('0.0000', '0.0000'),
        );

        $this->assertSame($orderLine, $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/currency 0
     */
    public function testHandlesTheDiscountAmountWhenItIsADatabaseString(): void
    {
        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'USD',
            ],
        ];

        $result = $instance->process(
            $orderLine,
            $this->createOrder(),
            $this->createFixedPriceBundleItem('10.0000', '0.0000'),
        );

        // 100 - 10 = 90, 21% VAT = 15.62
        $this->assertEquals(15.62, $result['vatAmount']['value']);
        $this->assertEquals(90, $result['totalAmount']['value']);
        $this->assertEquals(10, $result['discountAmount']['value']);
        $this->assertEquals('USD', $result['totalAmount']['currency']);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/currency 1
     */
    public function testHandlesTheBaseDiscountAmountWhenItIsADatabaseString(): void
    {
        /** @var BundleWithoutDynamicPricing $instance */
        $instance = $this->objectManager->create(BundleWithoutDynamicPricing::class);

        $orderLine = [
            'totalAmount' => [
                'value' => 100,
                'currency' => 'EUR',
            ],
        ];

        $result = $instance->process(
            $orderLine,
            $this->createOrder(),
            $this->createFixedPriceBundleItem('0.0000', '10.0000'),
        );

        // 100 - 10 = 90, 21% VAT = 15.62
        $this->assertEquals(15.62, $result['vatAmount']['value']);
        $this->assertEquals(90, $result['totalAmount']['value']);
        $this->assertEquals(10, $result['discountAmount']['value']);
        $this->assertEquals('EUR', $result['totalAmount']['currency']);
    }

    public function getBundleItem(OrderInterface $order): OrderItemInterface
    {
        $items = $order->getItems();
        foreach ($items as $item) {
            if ($item->getProductType() == Type::TYPE_BUNDLE) {
                return $item;
            }
        }

        $this->fail('Expected to find a bundle item');
    }

    private function createOrder(): OrderInterface
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setStoreId(1);
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('USD');

        return $order;
    }

    private function createFixedPriceBundleItem(
        string $discountAmount,
        string $baseDiscountAmount
    ): OrderItemInterface {
        /** @var ProductInterface $product */
        $product = $this->objectManager->create(ProductInterface::class);
        $product->setPriceType(Price::PRICE_TYPE_FIXED);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setProductType(Type::TYPE_BUNDLE);
        $orderItem->setProduct($product);
        $orderItem->setQtyOrdered('1.0000');
        $orderItem->setTaxPercent('21.0000');
        $orderItem->setDiscountAmount($discountAmount);
        $orderItem->setBaseDiscountAmount($baseDiscountAmount);

        return $orderItem;
    }
}
