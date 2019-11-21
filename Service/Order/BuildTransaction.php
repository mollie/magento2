<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;


use Magento\Sales\Api\Data\OrderInterface;

class BuildTransaction
{
    /**
     * @var TransactionPartInterface[]
     */
    private $parts;

    public function __construct(
        array $parts
    ) {
        $this->parts = $parts;
    }

    public function execute(OrderInterface $order, $apiMethod, array $transaction)
    {
        foreach ($this->parts as $part) {
            $transaction = $part->process($order, $apiMethod, $transaction);
        }

        return $transaction;
    }
}
