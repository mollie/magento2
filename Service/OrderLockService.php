<?php

namespace Mollie\Payment\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Mollie\Payment\Config;

class OrderLockService
{
    /**
     * @var OrderRepositoryInterfaceFactory
     */
    private $orderRepositoryFactory;

    /**
     * @var LockService
     */
    private $lockService;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        OrderRepositoryInterfaceFactory $orderRepositoryFactory,
        LockService $lockService,
        ResourceConnection $resourceConnection,
        Config $config
    ) {
        $this->orderRepositoryFactory = $orderRepositoryFactory;
        $this->lockService = $lockService;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
    }

    public function execute(OrderInterface $originalOrder, callable $callback)
    {
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

        // Save this value, so we can restore it after the order has been saved.
        $mollieTransactionId = $originalOrder->getMollieTransactionId();

        // The order repository uses caching to make sure it only loads the order once, but in this case we want
        // the latest version of the order, so we need to make sure we get a new instance of the repository.
        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->orderRepositoryFactory->create();
        $order = $orderRepository->get($originalOrder->getEntityId());

        // Restore the transaction ID as it might not be set on the saved order yet.
        // This is required further down the process.
        $order->setMollieTransactionId($mollieTransactionId);

        try {
            $result = $callback($order);
            $orderRepository->save($order);
            $connection->commit();

            // Update the original order with the new data.
            $originalOrder->setData($order->getData());
        } catch (\Exception $e) {
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

    private function getKeyName(OrderInterface $order): string
    {
        return 'mollie.order.' . $order->getEntityId();
    }
}
