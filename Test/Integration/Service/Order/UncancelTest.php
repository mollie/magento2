<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Mollie\Payment\Test\Integration\IntegrationTestCase;

class UncancelTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTheOrderTotals()
    {
        $order = $this->loadOrder('100000001');
        $order->cancel();

        foreach ($order->getItems() as $item) {
            $item->setSku(uniqid());
        }

        $this->assertEquals(100, $order->getSubtotalCanceled());
        $this->assertEquals(100, $order->getTotalCanceled());

        /** @var Uncancel $instance */
        $instance = $this->objectManager->create(Uncancel::class);
        $instance->execute($order);

        $this->assertEquals(0, $order->getSubtotalCanceled());
        $this->assertEquals(0, $order->getTotalCanceled());
    }
}
