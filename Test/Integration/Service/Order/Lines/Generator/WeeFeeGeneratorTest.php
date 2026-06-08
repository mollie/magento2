<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

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
}
