<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage;
use Mollie\Payment\Config;
use Mollie\Payment\Service\LockService;

class CancelOrder
{
    const LOCK_NAME = 'mollie.order.cancel.%s';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LockService
     */
    private $lockService;

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
     * @var Coupon
     */
    private $coupon;

    /**
     * @var Usage
     */
    private $couponUsage;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        Config $config,
        LockService $lockService,
        OrderCommentHistory $orderCommentHistory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        Coupon $coupon,
        Usage $couponUsage,
        ResourceConnection $resource
    ) {
        $this->config = $config;
        $this->lockService = $lockService;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->orderManagement = $orderManagement;
        $this->orderRepository = $orderRepository;
        $this->coupon = $coupon;
        $this->couponUsage = $couponUsage;
        $this->resource = $resource;
    }

    public function execute(OrderInterface $order, $reason = null): bool
    {
        if (!$order->getId() || $order->getState() == Order::STATE_CANCELED) {
            return false;
        }

        $key = sprintf(static::LOCK_NAME, $order->getId());
        if ($this->lockService->isLocked($key)) {
            $this->config->addToLog('info', sprintf('Key "%s" is locked', $key));
            return false;
        }

        // Lock for 5 minutes.
        $this->config->addToLog('info', sprintf('Getting lock for key "%s"', $key));
        $this->lockService->lock($key, 5 * 60);

        if ($this->isAlreadyCancelled($order)) {
            return false;
        }

        try {
            $comment = __('The order was canceled');
            if ($reason !== null) {
                $comment = __('The order was canceled, reason: payment %1', $reason);
            }

            $this->config->addToLog('info', $order->getIncrementId() . ' ' . $comment);
            $this->orderCommentHistory->add($order, $comment);
            $order->getPayment()->setMessage($comment);
            $this->orderRepository->save($order);

            $this->orderManagement->cancel($order->getId());

            if ($order->getCouponCode()) {
                $this->resetCouponAfterCancellation($order);
            }
        } finally {
            $this->config->addToLog('info', sprintf('Unlocking key "%s"', $key));
            $this->lockService->unlock($key);
        }

        return true;
    }

    public function resetCouponAfterCancellation(OrderInterface $order)
    {
        $this->coupon->load($order->getCouponCode(), 'code');
        if (!$this->coupon->getId()) {
            return;
        }

        $this->coupon->setTimesUsed($this->coupon->getTimesUsed() - 1);
        $this->coupon->save();

        $customerId = $order->getCustomerId();
        if ($customerId) {
            $this->couponUsage->updateCustomerCouponTimesUsed($customerId, $this->coupon->getId(), false);
        }
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
