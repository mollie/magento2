<?php

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Service\Order\PaymentReminder;
use Mollie\Payment\Service\Order\SecondChanceEmail;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentReminderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotSendReminderWhenAlreadyPaid(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var PendingPaymentReminderInterface $pendingPaymentReminder */
        $pendingPaymentReminder = $this->objectManager->create(PendingPaymentReminderInterface::class);
        $pendingPaymentReminder->setOrderId($order->getId());

        $secondChanceMock = $this->createMock(SecondChanceEmail::class);
        $secondChanceMock->expects($this->never())->method('send');

        /** @var PaymentReminder $instance */
        $instance = $this->objectManager->create(PaymentReminder::class, [
            'secondChanceEmail' => $secondChanceMock,
        ]);

        $instance->send($pendingPaymentReminder);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotSendReminderWhenProductsNotInStock(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->save();

        foreach ($order->getItems() as $item) {
            $item->getProduct()->setStatus(Status::STATUS_DISABLED);
        }

        /** @var PendingPaymentReminderInterface $pendingPaymentReminder */
        $pendingPaymentReminder = $this->objectManager->create(PendingPaymentReminderInterface::class);
        $pendingPaymentReminder->setOrderId($order->getId());

        $secondChanceMock = $this->createMock(SecondChanceEmail::class);
        $secondChanceMock->expects($this->never())->method('send');

        /** @var PaymentReminder $instance */
        $instance = $this->objectManager->create(PaymentReminder::class, [
            'secondChanceEmail' => $secondChanceMock,
        ]);

        $instance->send($pendingPaymentReminder);
    }
}
