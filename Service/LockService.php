<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service;

use Magento\Framework\Lock\LockManagerInterface;
use Mollie\Payment\Config;

class LockService
{
    private bool $alreadyLocked = false;

    public function __construct(
        private Config $config,
        private LockManagerInterface $lockManager
    ) {}

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
        // Make sure we only lock once per request.
        if ($this->alreadyLocked) {
            return true;
        }

        $message = 'Locking: ' . $name . ($reason ? ' - Reason: ' . $reason : '');
        $this->config->addToLog('info', $message);

        return $this->alreadyLocked = $this->lockManager->lock($name, $timeout);
    }

    /**
     * Releases a lock
     *
     * @param string $name lock name
     * @return bool
     */
    public function unlock(string $name): bool
    {
        $this->config->addToLog('info', 'Unlocking: ' . $name);

        $result = $this->lockManager->unlock($name);
        $this->alreadyLocked = !$result;

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
        while ($this->isLocked($name)) {
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
