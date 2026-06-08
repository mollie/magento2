<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\ProcessTransaction;

class ProcessTransactionFake extends ProcessTransaction
{
    private int $timesCalled = 0;

    private ?GetMollieStatusResult $response = null;

    public function getTimesCalled(): int
    {
        return $this->timesCalled;
    }

    public function setResponse(GetMollieStatusResult $response): void
    {
        $this->response = $response;
    }

    public function execute(int $orderId, string $transactionId, string $type = 'webhook'): GetMollieStatusResult
    {
        $this->timesCalled++;

        if ($this->response) {
            return $this->response;
        }

        return parent::execute($orderId, $transactionId, $type);
    }
}
