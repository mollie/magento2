<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Queue\Publisher;

use Mollie\Payment\Api\Data\TransactionToProcessInterface;
use Mollie\Payment\Queue\Publisher\PublishTransactionToProcess;

class PublishTransactionToProcessFake extends PublishTransactionToProcess
{
    private int $timesCalled = 0;

    private bool $publish = true;

    public function getTimesCalled(): int
    {
        return $this->timesCalled;
    }

    public function preventPublish(): void
    {
        $this->publish = false;
    }

    public function publish(TransactionToProcessInterface $data): void
    {
        $this->timesCalled++;

        if (!$this->publish) {
            return;
        }

        parent::publish($data);
    }
}
