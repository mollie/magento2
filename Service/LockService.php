<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Mollie\Payment\Config;

class LockService
{
    /**
     * @var array<string, int>
     */
    private array $activeLocks = [];

    public function __construct(
        private Config $config,
        private LockManagerInterface $lockManager
    ) {}

    /**
     * Runs the callback while the lock is held, and releases the lock afterwards.
     *
     * @param string $name lock name
     * @param callable $callback
     * @param ?string $reason Reason for locking, will be logged only
     * @return mixed
     * @throws LocalizedException
     */
    public function executeWhileLocked(string $name, callable $callback, ?string $reason = null)
    {
        if ($this->checkIfIsLockedWithWait($name)) {
            throw new LocalizedException(__('Unable to get lock for %1', $name));
        }

        $this->lock($name, -1, $reason);

        try {
            return $callback();
        } finally {
            $this->unlock($name);
        }
    }

    /**
     * Sets a lock
     *
     * @param string $name lock name
     * @param int $timeout How long to wait lock acquisition in seconds, negative value means infinite timeout
     * @param ?string $reason Reason for locking, will be logged only
     * @return bool
     */
    public function lock(string $name, int $timeout = -1, ?string $reason = null): bool
    {
        // The lock is re-entrant: only the outermost call acquires the underlying lock.
        if (isset($this->activeLocks[$name])) {
            $this->activeLocks[$name]++;

            return true;
        }

        $message = 'Locking: ' . $name . ($reason ? ' - Reason: ' . $reason : '');
        $this->config->addToLog('info', $message);

        $result = $this->lockManager->lock($name, $timeout);
        if ($result) {
            $this->activeLocks[$name] = 1;
        }

        return $result;
    }

    /**
     * Releases a lock
     *
     * @param string $name lock name
     * @return bool
     */
    public function unlock(string $name): bool
    {
        // Only release the underlying lock once the outermost caller is done with it.
        if (isset($this->activeLocks[$name]) && $this->activeLocks[$name] > 1) {
            $this->activeLocks[$name]--;

            return true;
        }

        $this->config->addToLog('info', 'Unlocking: ' . $name);

        $result = $this->lockManager->unlock($name);
        if ($result) {
            unset($this->activeLocks[$name]);
        }

        return $result;
    }

    /**
     * Tests if lock is set
     *
     * @param string $name lock name
     * @return bool
     */
    public function isLocked(string $name): bool
    {
        return $this->lockManager->isLocked($name);
    }

    /**
     * Tests if the lock is held by another process, ignoring the locks this request owns itself.
     *
     * @param string $name lock name
     * @return bool
     */
    public function isLockedByAnotherProcess(string $name): bool
    {
        if (isset($this->activeLocks[$name])) {
            return false;
        }

        return $this->isLocked($name);
    }

    /**
     * Try to get a lock, and if not, try $attempts times to get it.
     *
     * @param string $name
     * @param int $attempts
     * @return bool
     */
    public function checkIfIsLockedWithWait(string $name, int $attempts = 5): bool
    {
        $count = 0;
        $waitTime = 0;
        while ($this->isLockedByAnotherProcess($name)) {
            $waitTime += 500000;
            $this->config->addToLog(
                'info',
                sprintf(
                    'Lock for "%s" is already active, attempt %d (sleep for: %01.1F)',
                    $name,
                    $count,
                    $waitTime / 1000000,
                ),
            );

            usleep($waitTime);
            $count++;

            if ($count > $attempts) {
                return true;
            }
        }

        return false;
    }
}
