<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Mollie\Payment\Config;

/**
 * This class is meant as an alternative implantation of:
 * @see LockManagerInterface
 *
 * As it is only available in 2.3 and higher, but we need 2.2 support.
 *
 * Class LockService
 * @package Mollie\Payment\Service
 */
class LockService
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var LockManagerInterface
     */
    private $lockManager = null;

    /**
     * @var AdapterInterface|null
     */
    private $connection = null;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var bool
     */
    private $alreadyLocked = false;

    public function __construct(
        Config $config,
        ResourceConnection $resourceConnection
    ) {
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
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
        // Make sure we only lock once per request.
        if ($this->alreadyLocked) {
            return true;
        }

        $message = 'Locking: ' . $name . ($reason ? ' - Reason: ' . $reason : '');
        $this->config->addToLog('info', $message);
        if ($this->isLockManagerAvailable()) {
            return $this->alreadyLocked = $this->lockManager->lock($name, $timeout);
        }

        $result = (bool)$this->getConnection()->query(
            "SELECT GET_LOCK(?, ?);",
            [$name, $timeout < 0 ? 60 * 60 * 24 * 7 : $timeout]
        )->fetchColumn();

        if ($result) {
            $this->alreadyLocked = true;
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
        $this->config->addToLog('info', 'Unlocking: ' . $name);
        if ($this->isLockManagerAvailable()) {
            $result = $this->lockManager->unlock($name);
            $this->alreadyLocked = !$result;
            return $result;
        }

        $result = (bool)$this->getConnection()->query(
            "SELECT RELEASE_LOCK(?);",
            [(string)$name]
        )->fetchColumn();

        if ($result) {
            $this->alreadyLocked = false;
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
        if ($this->isLockManagerAvailable()) {
            return $this->lockManager->isLocked($name);
        }

        return (bool)$this->getConnection()->query(
            "SELECT IS_USED_LOCK(?);",
            [$name]
        )->fetchColumn();
    }

    /**
     * Try to get a lock, and if not, try $attempts times to get it.
     *
     * @param string $name
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
                    $waitTime / 1000000
                )
            );

            usleep($waitTime);
            $count++;

            if ($count > $attempts) {
                return true;
            }
        }

        return false;
    }

    private function isLockManagerAvailable(): bool
    {
        if ($this->lockManager !== null) {
            return $this->lockManager !== false;
        }

        try {
            // Because we need 2.2 support we are forced to use the ObjectManager. See the explanation above the class
            // for more information.
            $this->lockManager = ObjectManager::getInstance()->get(LockManagerInterface::class);
            return true;
        } catch (\ReflectionException $exception) {
            $this->lockManager = false;
            return false;
        }
    }

    private function getConnection(): AdapterInterface
    {
        if ($this->connection) {
            return $this->connection;
        }

        $this->connection = $this->resourceConnection->getConnection();

        return $this->connection;
    }
}
