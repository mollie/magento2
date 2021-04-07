<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Model\Order;
use Mollie\Payment\Service\LockService;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CancelOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRegisterCancellationCancelsTheOrder()
    {
        $order = $this->loadOrderById('100000001');

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $instance->execute($order);

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRegisterCancellationSetsTheCorrectMessage()
    {
        $order = $this->loadOrderById('100000001');

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $instance->execute($order, 'canceled');

        $payment = $order->getPayment();

        $this->assertEquals('The order was canceled, reason: payment canceled', $payment->getMessage()->render());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenLocked()
    {
        $version = $this->objectManager->create(\Magento\Framework\App\ProductMetadata::class)->getVersion();
        if (version_compare($version, '2.4', 'ge')) {
            $this->markTestSkipped('This test does not work op 2.4 and higher as we get a DummyLocker');
        }

        $order = $this->loadOrderById('100000001');

        $lockKey = sprintf(CancelOrder::LOCK_NAME, $order->getId());
        $this->objectManager->create(LockService::class)->lock($lockKey);

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $instance->execute($order);

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }
}
