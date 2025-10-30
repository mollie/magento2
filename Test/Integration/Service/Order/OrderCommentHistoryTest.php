<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderCommentHistoryTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddingHistory(): void
    {
        $message = '[TEST] a brand new status';

        $order = $this->loadOrder('100000001');
        $this->assertCount(0, $order->getStatusHistories());

        /** @var OrderCommentHistory $instance */
        $instance = $this->objectManager->create(OrderCommentHistory::class);
        $instance->add($order, __($message));

        $order->setStatusHistories(null);
        $histories = $order->getStatusHistories();
        $this->assertCount(1, $histories);

        /** @var OrderStatusHistoryInterface $mostRecentHistory */
        $mostRecentHistory = array_shift($histories);

        $this->assertEquals($message, $mostRecentHistory->getComment());
    }
}
