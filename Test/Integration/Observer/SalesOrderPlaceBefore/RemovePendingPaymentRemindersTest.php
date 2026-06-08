<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\SalesOrderPlaceBefore;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Observer\SalesOrderPlaceBefore\RemovePendingPaymentReminders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RemovePendingPaymentRemindersTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 0
     */
    public function testDoesNothingWhenDisabled(): void
    {
        $repository = $this->objectManager->get(PendingPaymentReminderRepositoryInterface::class);

        $reminder = $this->objectManager->get(PendingPaymentReminderInterfaceFactory::class)->create();
        $reminder->setOrderId(99999);
        $reminder->setCustomerId(1);
        $saved = $repository->save($reminder);

        $order = $this->objectManager->create(OrderInterface::class);
        $order->setCustomerId(1);
        $order->setCustomerEmail('test@example.com');

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        $this->objectManager->create(RemovePendingPaymentReminders::class)->execute($observer);

        $this->assertSame($saved->getEntityId(), $repository->get($saved->getEntityId())->getEntityId());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 1
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 1
     */
    public function testDoesNothingWhenNoEmailIsAvailable(): void
    {
        $repository = $this->objectManager->get(PendingPaymentReminderRepositoryInterface::class);

        $reminder = $this->objectManager->get(PendingPaymentReminderInterfaceFactory::class)->create();
        $reminder->setOrderId(99999);
        $reminder->setCustomerId(1);
        $saved = $repository->save($reminder);

        $order = $this->objectManager->create(OrderInterface::class);
        $order->setCustomerId(1);
        $order->setCustomerEmail(null);

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        $this->objectManager->create(RemovePendingPaymentReminders::class)->execute($observer);

        $this->assertSame($saved->getEntityId(), $repository->get($saved->getEntityId())->getEntityId());
    }
}
