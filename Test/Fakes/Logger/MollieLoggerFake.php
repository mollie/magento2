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

    public function addInfoLog(string $type, $data): void
    {
        $this->infoLogs[] = ['type' => $type, 'data' => $data];
    }

    public function getInfoLogs(): array
    {
        return $this->infoLogs;
    }
}
