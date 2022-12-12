<?php

namespace Mollie\Payment\Test\Integration\Observer\ControllerActionPredispatchCheckoutIndexIndex;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Observer\ControllerActionPredispatchCheckoutIndexIndex\RestoreQuoteOfUnsuccessfulPayment;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RestoreQuoteOfUnsuccessfulPaymentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNothingWhenPaymentMethodIsNotMollie(): void
    {
        /** @var OrderInterface $order */
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Checkmo::PAYMENT_METHOD_CHECKMO_CODE);

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
    public function testDoesNotRestoreIfPaymentIsMollieButMollieSuccessIsSet(): void
    {
        $order = $this->loadOrderById('100000001');
        $payment = $order->getPayment();
        $payment->setMethod(Ideal::CODE);
        $payment->setAdditionalInformation('mollie_success', true);

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
    public function testRestoresQuoteWhenMollieSuccessIsFalse(): void
    {
        $order = $this->loadOrderById('100000001');

        $payment = $order->getPayment();
        $payment->setMethod(Ideal::CODE);
        $payment->setAdditionalInformation('mollie_success', false);

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
    public function testDoesNotRestoresQuoteWhenMollieSuccessIsNotSet(): void
    {
        $order = $this->loadOrderById('100000001');

        $payment = $order->getPayment();
        $payment->setMethod(Ideal::CODE);
        $payment->unsAdditionalInformation('mollie_success');

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('getLastRealOrder')->willReturn($order);
        $sessionMock->expects($this->never())->method('restoreQuote');

        $instance = $this->objectManager->create(RestoreQuoteOfUnsuccessfulPayment::class, [
            'checkoutSession' => $sessionMock,
        ]);

        $instance->execute(new Observer([]));
    }
}
