<?php

namespace Mollie\Payment\Test\Integration\Observer\SalesOrderPaymentPlaceEnd;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Mollie\Payment\Observer\SalesOrderPaymentPlaceEnd\SetOrderStateToPendingPayment;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SetOrderStateToPendingPaymentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenTheMethodIsNotMollie(): void
    {
        /** @var SetOrderStateToPendingPayment $instance */
        $instance = $this->objectManager->get(SetOrderStateToPendingPayment::class);

        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('checkmo');
        $order->setState(Order::STATE_PAYMENT_REVIEW);

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('payment', $order->getPayment());

        $instance->execute($observer);

        $this->assertEquals(Order::STATE_PAYMENT_REVIEW, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testWhenTheMethodIsMollieTheStateIsChanged(): void
    {
        /** @var SetOrderStateToPendingPayment $instance */
        $instance = $this->objectManager->get(SetOrderStateToPendingPayment::class);

        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_ideal');
        $order->setState(Order::STATE_PAYMENT_REVIEW);

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('payment', $order->getPayment());

        $instance->execute($observer);

        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
    }
}
