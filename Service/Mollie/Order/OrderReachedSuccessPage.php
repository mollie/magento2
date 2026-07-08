<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;

class OrderReachedSuccessPage
{
    public function __construct(
        private readonly TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
    ) {
    }

    public function execute(OrderInterface $order): bool
    {
        $this->searchCriteriaBuilder->addFilter(TransactionToOrderInterface::ORDER_ID, $order->getEntityId());
        $result = $this->transactionToOrderRepository->getList($this->searchCriteriaBuilder->create());

        $redirectedToSuccessPage = array_filter(
            $result->getItems(),
            static fn (TransactionToOrderInterface $item): bool => (int) $item->getRedirected() === 1
        );

        return $redirectedToSuccessPage !== [];
    }
}
