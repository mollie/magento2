<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
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
        $this->objectManager->create(SortOrderFactory::class);

        $fake = new class extends SortOrderFactory {
            public function __construct() {}

            public function create(array $data = [])
            {
                throw new \Exception('This should not be called');
            }
        };

        /** @var SendPendingPaymentReminders $sendReminderJob */
        $sendReminderJob = $this->objectManager->create(SendPendingPaymentReminders::class, [
            'sortOrderFactory' => $fake,
        ]);
        $sendReminderJob->execute();
    }
}
