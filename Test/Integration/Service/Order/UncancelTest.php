<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Service\Order\Uncancel;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class UncancelTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTheOrderTotals(): void
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
