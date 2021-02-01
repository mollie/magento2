<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Cron;

use Magento\Framework\Api\SortOrderFactory;
use Mollie\Payment\Cron\SendPendingPaymentReminders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SendPendingPaymentRemindersTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 1
     */
    public function testSecondChanceDisabledAutoSendEnabled()
    {
        /** @var SortOrderFactory|MockObject $sortOrderFactoryMock */
        $sortOrderFactoryMock = $this->getMockBuilder(SortOrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $sortOrderFactoryMock->expects($this->never())->method('create');

        $this->objectManager->addSharedInstance(
            $sortOrderFactoryMock,
            SortOrderFactory::class
        );

        /** @var SendPendingPaymentReminders $sendReminderJob */
        $sendReminderJob = $this->objectManager->get(SendPendingPaymentReminders::class);
        $sendReminderJob->execute();
    }
}