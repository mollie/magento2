<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Cron;

use Exception;
use Magento\Framework\Api\SortOrderFactory;
use Mollie\Payment\Cron\SendPendingPaymentReminders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SendPendingPaymentRemindersTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 0
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 1
     */
    public function testSecondChanceDisabledAutoSendEnabled(): void
    {
        $this->objectManager->create(SortOrderFactory::class);

        $fake = new class() extends SortOrderFactory {
            public function __construct()
            {
            }

            public function create(array $data = []): never
            {
                throw new Exception('This should not be called');
            }
        };

        /** @var SendPendingPaymentReminders $sendReminderJob */
        $sendReminderJob = $this->objectManager->create(SendPendingPaymentReminders::class, [
            'sortOrderFactory' => $fake,
        ]);
        $sendReminderJob->execute();
    }
}
