<?php

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\TestFramework\ObjectManager;
use Mollie\Payment\Test\Integration\TestCase;

class OrderCommentHistoryTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddingHistory()
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
