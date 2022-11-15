<?php

namespace Mollie\Payment\Test\Integration\Observer\ControllerActionPredispatchCheckoutIndexIndex;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Observer\ControllerActionPredispatchCheckoutIndexIndex\RestoreQuoteOfUnsuccessfulPayment;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RestoreQuoteOfUnsuccessfulPaymentTest extends IntegrationTestCase
{
    public function testDoesNothingWhenConditionAreNotMet(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getLastRealOrder')->willReturn($order);
        $sessionMock->expects($this->never())->method('restoreQuote');

        $instance = $this->objectManager->create(RestoreQuoteOfUnsuccessfulPayment::class, [
            'checkoutSession' => $sessionMock,
        ]);

        $instance->execute(new Observer([]));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotRestoreIfNotPending(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);
        $order->setState(Order::STATE_PROCESSING); // Anything but pending

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getLastRealOrder')->willReturn($order);
        $sessionMock->expects($this->never())->method('restoreQuote');

        $instance = $this->objectManager->create(RestoreQuoteOfUnsuccessfulPayment::class, [
            'checkoutSession' => $sessionMock,
        ]);

        $instance->execute(new Observer([]));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testRestoresQuote(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus('pending_payment');

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getLastRealOrder')->willReturn($order);
        $sessionMock->expects($this->once())->method('restoreQuote');

        $instance = $this->objectManager->create(RestoreQuoteOfUnsuccessfulPayment::class, [
            'checkoutSession' => $sessionMock,
        ]);

        $instance->execute(new Observer([]));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesRestoreWithin5Minutes(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus('pending_payment');
        $now = new \DateTimeImmutable();
        $order->setCreatedAt($now->sub(new \DateInterval('PT3M'))->format('Y-m-d H:i:s'));

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getLastRealOrder')->willReturn($order);
        $sessionMock->expects($this->once())->method('restoreQuote');

        $instance = $this->objectManager->create(RestoreQuoteOfUnsuccessfulPayment::class, [
            'checkoutSession' => $sessionMock,
        ]);

        $instance->execute(new Observer([]));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesAfterMoreThan5Minutes(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus('pending_payment');
        $now = new \DateTimeImmutable();
        $order->setCreatedAt($now->sub(new \DateInterval('PT10M'))->format('Y-m-d H:i:s'));

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getLastRealOrder')->willReturn($order);
        $sessionMock->expects($this->never())->method('restoreQuote');

        $instance = $this->objectManager->create(RestoreQuoteOfUnsuccessfulPayment::class, [
            'checkoutSession' => $sessionMock,
        ]);

        $instance->execute(new Observer([]));
    }
}
