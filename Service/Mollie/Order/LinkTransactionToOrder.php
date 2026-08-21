<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\ResourceModel\TransactionToOrder;

class LinkTransactionToOrder
{
    public function __construct(
        private TransactionToOrder $transactionToOrderResource
    ) {}

    public function execute(string $transactionId, OrderInterface $order): void
    {
        $order->setMollieTransactionId($transactionId);

        $this->transactionToOrderResource->link($transactionId, (int)$order->getEntityId());
    }
}
