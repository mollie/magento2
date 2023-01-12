<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Model\Order;
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
}
