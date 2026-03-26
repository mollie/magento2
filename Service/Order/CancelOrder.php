<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Magento\Order\CancelRewardPoints;
use Psr\Log\LoggerInterface;

class CancelOrder
{
    /**
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CancelRewardPoints
     */
    private $cancelRewardPoints;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var StatusResolver
     */
    private $statusResolver;
    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        Config $config,
        CancelRewardPoints $cancelRewardPoints,
        OrderCommentHistory $orderCommentHistory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        StatusResolver $statusResolver,
        ResourceConnection $resource,
        // BEGIN PATCH
        LoggerInterface $logger,
        CacheInterface $cache
        // END PATCH
    )
    {
        $this->cache = $cache;
        $this->config = $config;
        $this->cancelRewardPoints = $cancelRewardPoints;
        $this->logger = $logger;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->statusResolver = $statusResolver;
        $this->resource = $resource;
    }

    public function execute(OrderInterface $order, $reason = null): bool
    {
        $lockName = sprintf('cancel_order_%s', $order->getIncrementId());

        $lockExists = $this->cache->load($lockName);
        $lockSet = false;

        if (!$lockExists) {
            $lockSet = $this->cache->save(1, $lockName, [], 100);
        }

        if ($lockExists || $lockSet) {
            $this->logger->info(
                sprintf('Cannot cancel order %s. DB lock is set ', $order->getIncrementId())
            );
        }

        if (!$lockSet && $lockExists) {
            sleep(5);
            return $this->execute($order, $reason);
        }

        if (!$order->getId() || $order->getState() == Order::STATE_CANCELED) {
            return false;
        }

        if ($this->isAlreadyCancelled($order)) {
            return false;
        }

        $comment = __('The order was canceled');
        if ($reason !== null) {
            $comment = __('The order was canceled, reason: payment %1', $reason);
        }

        $order->setStatus($this->statusResolver->getOrderStatusByState($order, Order::STATE_CANCELED));
        $this->config->addToLog('info', $order->getIncrementId() . ' ' . $comment);
        $this->orderCommentHistory->add($order, $comment);
        $order->getPayment()->setMessage($comment);
        $this->orderRepository->save($order);

        $this->orderManagement->cancel($order->getId());
        $this->cancelRewardPoints->execute($order);

        $this->cache->remove($lockName);

        return true;
    }

    /**
     * It is possible that the order is cancelled in process A, and we are checking here in process B. So always check
     * the latest status in the database so we are sure we have the most recent status available.
     *
     * @param OrderInterface $order
     * @return bool
     */
    protected function isAlreadyCancelled(OrderInterface $order): bool
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('sales_order');

        $state = $connection->fetchOne('select `state` from ' . $table . ' where `entity_id` = :entity_id limit 1', [
            'entity_id' => $order->getEntityId(),
        ]);

        return $state == Order::STATE_CANCELED;
    }
}
