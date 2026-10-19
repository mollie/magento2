<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * A shared transaction pays for several orders with one Mollie payment. Releasing the authorization releases the
 * remaining amount of the whole payment, so it must wait until no other order still has to be captured.
 */
class IsAuthorizationNeededByOtherOrders
{
    public function __construct(
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function execute(OrderInterface $order): bool
    {
        $transactionId = (string) $order->getMollieTransactionId();
        if ($transactionId === '') {
            return false;
        }

        $searchCriteria = $this->searchCriteriaBuilderFactory->create()
            ->addFilter('mollie_transaction_id', $transactionId)
            ->addFilter('entity_id', $order->getEntityId(), 'neq')
            ->addFilter('state', Order::STATE_CANCELED, 'neq')
            ->create();

        $ordersAwaitingCapture = array_filter(
            $this->orderRepository->getList($searchCriteria)->getItems(),
            fn (OrderInterface $otherOrder): bool => $this->isAwaitingCapture($otherOrder),
        );

        return $ordersAwaitingCapture !== [];
    }

    private function isAwaitingCapture(OrderInterface $order): bool
    {
        return (float) $order->getBaseTotalInvoiced() < (float) $order->getBaseGrandTotal();
    }
}
