<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;

class BuildTransaction
{
    public function __construct(
        /**
         * @var TransactionPartInterface[]
         */
        private array $parts,
    ) {
    }

    public function execute(OrderInterface $order, array $transaction): array
    {
        foreach ($this->parts as $part) {
            $transaction = $part->process($order, $transaction);
        }

        return $transaction;
    }
}
