<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderContainsSubscriptionProductTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsFalseWhenTheOrderDoesNotContainSubscriptionProducts(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var OrderContainsSubscriptionProduct $instance */
        $instance = $this->objectManager->create(OrderContainsSubscriptionProduct::class);

        $this->assertFalse($instance->check($order));
    }
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsTrueWhenTheOrderDoesContainSubscriptionProducts(): void
    {
        $order = $this->loadOrder('100000001');
        $items = $order->getItems();
        $item = array_shift($items);

        $item->setProductOptions(['info_buyRequest' => [
            'qty' => '1',
            'mollie_metadata' => [
                'is_recurring' => 1,
            ],
        ]]);

        /** @var OrderContainsSubscriptionProduct $instance */
        $instance = $this->objectManager->create(OrderContainsSubscriptionProduct::class);

        $this->assertTrue($instance->check($order));
    }
}
