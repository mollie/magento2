<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Mollie\Payment\Api\TransactionToOrderManagementInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;

class TransactionToOrderManagement implements TransactionToOrderManagementInterface
{
    public function __construct(
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private TransactionToOrderRepositoryInterface $transactionToOrderRepository
    ) {}

    public function getForOrder(int $entityId): array
    {
        $this->searchCriteriaBuilder->addFilter('order_id', $entityId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $transactionToOrders = $this->transactionToOrderRepository->getList($searchCriteria);

        return $transactionToOrders->getItems();
    }
}
