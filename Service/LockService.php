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

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Sets a lock
     *
     * @param string $name lock name
     * @param int $timeout How long to wait lock acquisition in seconds, negative value means infinite timeout
     * @return bool
     */
    public function lock(string $name, int $timeout = -1): bool
    {
        if ($this->isLockManagerAvailable()) {
            return $this->lockManager->lock($name, $timeout);
        }

        $result = (bool)$this->getConnection()->query(
            "SELECT GET_LOCK(?, ?);",
            [$name, $timeout < 0 ? 60 * 60 * 24 * 7 : $timeout]
        )->fetchColumn();

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
        if ($this->isLockManagerAvailable()) {
            return $this->lockManager->unlock($name);
        }

        $result = (bool)$this->getConnection()->query(
            "SELECT RELEASE_LOCK(?);",
            [(string)$name]
        )->fetchColumn();

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
