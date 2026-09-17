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

class GetOrderByTransactionId
{
    public function __construct(
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function execute(string $transactionId): ?OrderInterface
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('mollie_transaction_id', $transactionId);

        $orders = $this->orderRepository->getList($searchCriteriaBuilder->create())->getItems();

        return $orders === [] ? null : array_shift($orders);
    }
}
