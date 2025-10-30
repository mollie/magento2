<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes;

use Mollie\Payment\Config;

class ConfigFake extends Config
{
    private array $loggedMessages = [];

    public function addTolog(string $type, $message): void
    {
        parent::addToLog($type, $message);

        $this->loggedMessages[] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function getLoggedMessages(): array
    {
        return $this->loggedMessages;
    }
}
