<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Mollie\Payment\Config;
use Throwable;

class OrderLockService
{
    public function __construct(
        private OrderRepositoryInterfaceFactory $orderRepositoryFactory,
        private LockService $lockService,
        private ResourceConnection $resourceConnection,
        private Config $config
    ) {}

    /**
     * @template T
     * @param callable(OrderInterface): T $callback
     * @return T The result of the callback
     */
    public function execute(OrderInterface $originalOrder, callable $callback): mixed
    {
        $orderId = (int)$originalOrder->getEntityId();
        $key = $this->getKeyName($originalOrder);
        if ($this->lockService->checkIfIsLockedWithWait($key)) {
            throw new LocalizedException(__('Unable to get lock for %1', $key));
        }

        $this->lockService->lock($key);

        // Defaults to the "default" connection when there is no connection available named "sales".
        // This is required for stores with a split database (Enterprise only):
        // https://devdocs.magento.com/guides/v2.3/config-guide/multi-master/multi-master.html
        $connection = $this->resourceConnection->getConnection('sales');
        $connection->beginTransaction();

        try {
            // This must be the first statement in the transaction. Another process (for example a credit memo
            // being refunded) can be mid-transaction on this order. Without the row lock, the read below returns
            // the snapshot from before their commit and the save() writes that outdated state back over their result.
            $this->lockOrderRow($connection, $orderId);

            // Save this value, so we can restore it after the order has been saved.
            $mollieTransactionId = $originalOrder->getMollieTransactionId();

            // The order repository uses caching to make sure it only loads the order once, but in this case we want
            // the latest version of the order, so we need to make sure we get a new instance of the repository.
            /** @var OrderRepositoryInterface $orderRepository */
            $orderRepository = $this->orderRepositoryFactory->create();
            $order = $orderRepository->get($orderId);

            // Restore the transaction ID as it might not be set on the saved order yet.
            // This is required further down the process.
            $order->setMollieTransactionId($mollieTransactionId);

            $result = $callback($order);
            $orderRepository->save($order);
            $connection->commit();

            // Update the original order with the new data.
            $originalOrder->setData($order->getData());
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        } finally {
            $this->lockService->unlock($key);
            $this->config->addToLog('info', sprintf('Key "%s" unlocked', $key));
        }

        return $result;
    }

    public function isLocked(OrderInterface $order): bool
    {
        $key = $this->getKeyName($order);

        return $this->lockService->isLocked($key);
    }

    private function lockOrderRow(AdapterInterface $connection, int $orderId): void
    {
        $connection->query(
            $connection->select()
                ->from($this->resourceConnection->getTableName('sales_order'), 'entity_id')
                ->where('entity_id = ?', $orderId)
                ->forUpdate(true)
        );
    }

    private function getKeyName(OrderInterface $order): string
    {
        return 'mollie.order.' . $order->getEntityId();
    }
}
