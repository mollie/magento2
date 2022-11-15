<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

use Mollie\Payment\Service\Order\Lines\Generator\MageWorxRewardPoints;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MageWorxRewardPointsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesnothingWhenNotApplicable()
    {
        $order = $this->loadOrder('100000001');

        /** @var MageWorxRewardPoints $instance */
        $instance = $this->objectManager->create(MageWorxRewardPoints::class);

        $this->assertCount(0, $instance->process($order, []));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsANegativeValue()
    {
        $order = $this->loadOrder('100000001');
        $order->setMwRwrdpointsAmnt(99);

        /** @var MageWorxRewardPoints $instance */
        $instance = $this->objectManager->create(MageWorxRewardPoints::class);

        $result = $instance->process($order, []);
        $this->assertCount(1, $result);

        $result = end($result);
        $this->assertEquals(-99, $result['unitPrice']['value']);
    }
}
