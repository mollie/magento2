<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Observer\SalesOrderPlaceBefore;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Observer\SalesOrderPlaceBefore\RemovePendingPaymentReminders;
use Mollie\Payment\Service\Order\DeletePaymentReminder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RemovePendingPaymentRemindersTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 0
     */
    public function testDoesNothingWhenDisabled()
    {
        $deletePaymentReminderMock = $this->createMock(DeletePaymentReminder::class);
        $deletePaymentReminderMock->expects($this->never())->method('delete');

        /** @var RemovePendingPaymentReminders $instance */
        $instance = $this->objectManager->create(RemovePendingPaymentReminders::class, [
            'deletePaymentReminder' => $deletePaymentReminderMock,
        ]);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $this->objectManager->create(OrderInterface::class));

        $instance->execute($observer);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 1
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 1
     */
    public function testDoesNothingWhenNoEmailIsAvailable()
    {
        $deletePaymentReminderMock = $this->createMock(DeletePaymentReminder::class);
        $deletePaymentReminderMock->expects($this->never())->method('delete');

        /** @var RemovePendingPaymentReminders $instance */
        $instance = $this->objectManager->create(RemovePendingPaymentReminders::class, [
            'deletePaymentReminder' => $deletePaymentReminderMock,
        ]);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setCustomerEmail(null);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        $instance->execute($observer);
    }
}
