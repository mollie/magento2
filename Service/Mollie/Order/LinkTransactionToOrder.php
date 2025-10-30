<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterfaceFactory;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;

class LinkTransactionToOrder
{
    public function __construct(
        private TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        private TransactionToOrderInterfaceFactory $transactionToOrderFactory
    ) {}

    public function execute(string $transactionId, OrderInterface $order): void
    {
        $this->transactionToOrderRepository->save(
            $this->transactionToOrderFactory->create()
                ->setTransactionId($transactionId)
                ->setOrderId((int)$order->getEntityId()),
        );

        $order->setMollieTransactionId($transactionId);
    }
}
