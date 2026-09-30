<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

use Magento\Sales\Model\Order\Item;
use Mollie\Payment\Service\Order\Lines\Generator\WeeeFeeGenerator;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class WeeFeeGeneratorTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenNotWeee(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var WeeeFeeGenerator $instance */
        $instance = $this->objectManager->create(WeeeFeeGenerator::class);

        $result = $instance->process($order, []);

        $this->assertCount(0, $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsWeeeItems(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $orderItems = $order->getItems();

        $item = array_shift($orderItems);
        $item->setWeeeTaxApplied('[{"row_amount_incl_tax": "10", "base_row_amount_incl_tax": "10"}]');
        $item->setWeeeTaxAppliedAmount(10);

        /** @var WeeeFeeGenerator $instance */
        $instance = $this->objectManager->create(WeeeFeeGenerator::class);

        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $this->assertEquals(10.0, $result[0]['totalAmount']['value']);
        $this->assertEquals('surcharge', $result[0]['type']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCountsWeeeOnceForConfigurableProducts(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $parent = $this->createWeeeItem('2.85');
        $child = $this->createWeeeItem('2.85')->setParentItem($parent);
        $order->setItems([$parent, $child]);

        /** @var WeeeFeeGenerator $instance */
        $instance = $this->objectManager->create(WeeeFeeGenerator::class);
        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $this->assertEquals(2.85, $result[0]['totalAmount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCountsWeeeOfEveryBundleChild(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $parent = $this->createWeeeItem('5');
        $firstChild = $this->createWeeeItem('3')->setParentItem($parent);
        $secondChild = $this->createWeeeItem('5')->setParentItem($parent);
        $order->setItems([$parent, $firstChild, $secondChild]);

        /** @var WeeeFeeGenerator $instance */
        $instance = $this->objectManager->create(WeeeFeeGenerator::class);
        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $this->assertEquals(8.0, $result[0]['totalAmount']['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCountsWeeeOfTheParentWhenTheChildHasNoWeee(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $parent = $this->createWeeeItem('2.85');
        /** @var Item $child */
        $child = $this->objectManager->create(Item::class);
        $child->setParentItem($parent);
        $order->setItems([$parent, $child]);

        /** @var WeeeFeeGenerator $instance */
        $instance = $this->objectManager->create(WeeeFeeGenerator::class);
        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $this->assertEquals(2.85, $result[0]['totalAmount']['value']);
    }

    private function createWeeeItem(string $amount): Item
    {
        /** @var Item $item */
        $item = $this->objectManager->create(Item::class);
        $item->setWeeeTaxApplied(
            sprintf('[{"row_amount_incl_tax": "%1$s", "base_row_amount_incl_tax": "%1$s"}]', $amount)
        );
        $item->setWeeeTaxAppliedAmount((float) $amount);

        return $item;
    }
}
