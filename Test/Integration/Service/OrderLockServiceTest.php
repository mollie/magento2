<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnection\ConnectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use RuntimeException;
use Throwable;
use TypeError;

class OrderLockServiceTest extends IntegrationTestCase
{
    private const LOCK_WAIT_TIMEOUT_SECONDS = 1;

    private ?AdapterInterface $otherConnection = null;

    private ?int $originalLockWaitTimeout = null;

    protected function tearDownWithoutVoid()
    {
        $this->otherConnection?->rollBack();
        $this->otherConnection?->closeConnection();
        $this->otherConnection = null;

        $this->restoreTheLockWaitTimeout();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testKeepsTheTransactionId(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId('test_value');

        /** @var OrderLockService $instance */
        $instance = $this->objectManager->create(OrderLockService::class);

        $instance->execute($order, function ($order): void {
            $this->assertEquals('test_value', $order->getMollieTransactionId());
        });
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testRollsBackAndReleasesTheLockWhenTheCallbackThrowsAnException(): void
    {
        $order = $this->loadOrderById('100000001');
        $stateBefore = $order->getState();
        $transactionLevelBefore = $this->getSalesConnection()->getTransactionLevel();

        /** @var OrderLockService $instance */
        $instance = $this->objectManager->create(OrderLockService::class);

        $exception = $this->captureException(function () use ($instance, $order): void {
            $instance->execute($order, function (OrderInterface $order): void {
                $this->closeAndSaveTheOrder($order);

                throw new RuntimeException('Something went wrong in the callback');
            });
        });

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertFalse($instance->isLocked($order));
        $this->assertSame($transactionLevelBefore, $this->getSalesConnection()->getTransactionLevel());
        $this->assertSame($stateBefore, $this->loadOrderById('100000001')->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testRollsBackAndReleasesTheLockWhenTheCallbackThrowsAnError(): void
    {
        $order = $this->loadOrderById('100000001');
        $stateBefore = $order->getState();
        $transactionLevelBefore = $this->getSalesConnection()->getTransactionLevel();

        /** @var OrderLockService $instance */
        $instance = $this->objectManager->create(OrderLockService::class);

        $exception = $this->captureException(function () use ($instance, $order): void {
            $instance->execute($order, function (OrderInterface $order): void {
                $this->closeAndSaveTheOrder($order);

                throw new TypeError('Something went wrong in the callback');
            });
        });

        $this->assertInstanceOf(TypeError::class, $exception);
        $this->assertFalse($instance->isLocked($order));
        $this->assertSame($transactionLevelBefore, $this->getSalesConnection()->getTransactionLevel());
        $this->assertSame($stateBefore, $this->loadOrderById('100000001')->getState());
    }

    /**
     * The other transaction simulates Magento's own credit memo flow: it has already updated the order row but has
     * not committed yet. Without the row lock, the service reads the outdated row and would write it back over the
     * refund once the other transaction commits. With the row lock, the service waits for the other transaction.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testWaitsForAnotherTransactionThatIsWritingTheOrder(): void
    {
        $order = $this->loadOrderById('100000001');
        $transactionLevelBefore = $this->getSalesConnection()->getTransactionLevel();
        $this->startAnotherTransactionThatUpdatesTheOrder($order);
        $this->limitTheLockWaitTimeout(self::LOCK_WAIT_TIMEOUT_SECONDS);

        /** @var OrderLockService $instance */
        $instance = $this->objectManager->create(OrderLockService::class);

        $exception = $this->captureException(function () use ($instance, $order): void {
            $instance->execute($order, function (): void {
                $this->fail('The callback should not run while another transaction holds the order row');
            });
        });

        $this->assertInstanceOf(LockWaitException::class, $exception);
        $this->assertFalse($instance->isLocked($order));
        $this->assertSame($transactionLevelBefore, $this->getSalesConnection()->getTransactionLevel());
    }

    private function captureException(callable $callable): ?Throwable
    {
        try {
            $callable();
        } catch (Throwable $exception) {
            return $exception;
        }

        return null;
    }

    private function closeAndSaveTheOrder(OrderInterface $order): void
    {
        $order->setState(Order::STATE_CLOSED);
        $order->setStatus(Order::STATE_CLOSED);

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
    }

    private function startAnotherTransactionThatUpdatesTheOrder(OrderInterface $order): void
    {
        $this->otherConnection = $this->createSeparateConnection();
        $this->otherConnection->beginTransaction();
        $this->otherConnection->update(
            $this->objectManager->get(ResourceConnection::class)->getTableName('sales_order'),
            ['state' => Order::STATE_CLOSED, 'status' => Order::STATE_CLOSED],
            ['entity_id = ?' => (int)$order->getEntityId()]
        );
    }

    private function createSeparateConnection(): AdapterInterface
    {
        $connectionConfig = $this->objectManager->get(DeploymentConfig::class)->get('db/connection/default');

        return $this->objectManager->get(ConnectionFactory::class)->create($connectionConfig);
    }

    private function limitTheLockWaitTimeout(int $seconds): void
    {
        $this->originalLockWaitTimeout = (int)$this->getSalesConnection()->fetchOne(
            'SELECT @@SESSION.innodb_lock_wait_timeout'
        );

        $this->getSalesConnection()->query('SET SESSION innodb_lock_wait_timeout = ' . $seconds);
    }

    private function restoreTheLockWaitTimeout(): void
    {
        if ($this->originalLockWaitTimeout === null) {
            return;
        }

        $this->getSalesConnection()->query(
            'SET SESSION innodb_lock_wait_timeout = ' . $this->originalLockWaitTimeout
        );
        $this->originalLockWaitTimeout = null;
    }

    private function getSalesConnection(): AdapterInterface
    {
        return $this->objectManager->get(ResourceConnection::class)->getConnection('sales');
    }
}
