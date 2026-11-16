<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Logger;

use Mollie\Payment\Logger\MollieLogger;

class MollieLoggerFake extends MollieLogger
{
    private array $infoLogs = [];

    private array $errorLogs = [];

    public function addInfoLog(string $type, $data): void
    {
        $this->infoLogs[] = ['type' => $type, 'data' => $data];
    }

    public function getInfoLogs(): array
    {
        return $this->infoLogs;
    }

    public function addErrorLog(string $type, $data): void
    {
        $this->errorLogs[] = ['type' => $type, 'data' => $data];
    }

    public function getErrorLogs(): array
    {
        return $this->errorLogs;
    }
}
