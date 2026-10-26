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

class IsSharedTransaction
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
            ->setPageSize(2)
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getTotalCount() > 1;
    }
}
