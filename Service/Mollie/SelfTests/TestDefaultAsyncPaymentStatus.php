<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\AsyncPaymentMethods;

class TestDefaultAsyncPaymentStatus extends AbstractSelfTest
{
    public function __construct(
        private Config $config,
        private AsyncPaymentMethods $asyncPaymentMethods,
    ) {}

    public function execute(): void
    {
        foreach ($this->asyncPaymentMethods->all() as $method) {
            if (!$this->config->isMethodActive($method)) {
                continue;
            }

            if ($this->config->statusPendingForMethod($method) !== 'pending_payment') {
                continue;
            }

            $this->addMessage('error', __(
                'Warning: We recommend to use a unique payment status for pending %1 payments',
                $this->config->getMethodTitle($method),
            ));
        }
    }
}
