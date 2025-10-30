<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\AreQueuesConfiguredCorrectly as AreQueuesConfiguredCorrectly;

class AreQueuesConfiguredCorrectlyTest extends AbstractSelfTest
{
    public function __construct(
        private Config $config,
        private AreQueuesConfiguredCorrectly $areQueuesConfiguredCorrectly
    ) {}

    public function execute(): void
    {
        if (!$this->config->processTransactionsInTheQueue()) {
            return;
        }

        if (!$this->areQueuesConfiguredCorrectly->execute()) {
            $this->addMessage(
                'error',
                __('The queues are not properly configured. Please check the configuration so that `mollie.transaction.processor` is allowed to run.'),
            );
        }
    }
}
