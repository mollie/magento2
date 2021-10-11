<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

use Mollie\Payment\Service\Order\Lines\Generator\WeeeFeeGenerator;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class WeeFeeGeneratorTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenNotWeee()
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
    public function testReturnsWeeeItems()
    {
        $order = $this->loadOrder('100000001');
        $orderItems = $order->getItems();

        $item = array_shift($orderItems);
        $item->setWeeeTaxAppliedAmount(10);
        $item->setBaseWeeeTaxAppliedAmount(10);

        /** @var WeeeFeeGenerator $instance */
        $instance = $this->objectManager->create(WeeeFeeGenerator::class);

        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $this->assertEquals(10.0, $result[0]['totalAmount']['value']);
        $this->assertEquals('surcharge', $result[0]['type']);
    }
}
