<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\AdminNotification\Model\Inbox;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mollie\Payment\Service\Mollie\AreQueuesConfiguredCorrectly;

class WarnIfQueueNotConfiguredCorrectly implements DataPatchInterface
{
    public function __construct(
        private AreQueuesConfiguredCorrectly $areQueuesConfiguredCorrectly,
        private Inbox $inbox,
    ) {}

    public function apply(): void
    {
        if ($this->areQueuesConfiguredCorrectly->execute()) {
            return;
        }

        $this->inbox->addCritical(
            (string)__('Mollie Payment: queue consumer not configured'),
            (string)__('Queue processing is enabled but "mollie.transaction.processor" is missing from your cron_consumers_runner/consumers whitelist in env.php. Webhooks will be enqueued but never processed, leaving orders stuck in pending_payment. Add "mollie.transaction.processor" to the whitelist, or disable queue processing under Stores > Configuration > Mollie > General.'),
        );
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
