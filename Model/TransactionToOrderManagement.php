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

class TransactionToOrderManagement implements  TransactionToOrderManagementInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var TransactionToOrderRepositoryInterface
     */
    private $transactionToOrderRepository;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionToOrderRepositoryInterface $transactionToOrderRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionToOrderRepository = $transactionToOrderRepository;
    }

    public function getForOrder(int $entityId): array
    {
        $this->searchCriteriaBuilder->addFilter('order_id', $entityId);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $transactionToOrders = $this->transactionToOrderRepository->getList($searchCriteria);

        return $transactionToOrders->getItems();
    }
}
