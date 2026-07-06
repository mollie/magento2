<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Framework\Lock;

use Magento\Framework\Lock\LockManagerInterface;

class LockManagerFake implements LockManagerInterface
{
    /**
     * @var array<string, bool>
     */
    private array $locks = [];

    /**
     * @var array<string, int>
     */
    private array $lockCallsByName = [];

    public function lock(string $name, int $timeout = -1): bool
    {
        $this->locks[$name] = true;
        $this->lockCallsByName[$name] = ($this->lockCallsByName[$name] ?? 0) + 1;

        return true;
    }

    public function unlock(string $name): bool
    {
        unset($this->locks[$name]);

        return true;
    }

    public function isLocked(string $name): bool
    {
        return isset($this->locks[$name]);
    }

    public function lockCallCount(string $name): int
    {
        return $this->lockCallsByName[$name] ?? 0;
    }
}
