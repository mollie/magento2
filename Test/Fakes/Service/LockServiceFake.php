<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service;

use Mollie\Payment\Service\LockService;

class LockServiceFake extends LockService
{
    private bool $lockIsHeldByAnotherProcess = false;

    public function givenTheLockIsHeldByAnotherProcess(): void
    {
        $this->lockIsHeldByAnotherProcess = true;
    }

    public function checkIfIsLockedWithWait(string $name, int $attempts = 5): bool
    {
        return $this->lockIsHeldByAnotherProcess;
    }
}
