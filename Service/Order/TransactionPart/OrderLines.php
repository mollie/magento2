<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\Order as OrderOrderLines;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class OrderLines implements TransactionPartInterface
{
    private array $methodsRequiringOrderLines = [
        'billie',
        'in3',
        'klarna',
        'riverty',
        'voucher',
    ];

    public function __construct(
        private OrderOrderLines $orderOrderLines
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        $method = str_replace('mollie_methods_', '', $order->getPayment()->getMethod());
        if (!in_array($method, $this->methodsRequiringOrderLines, true)) {
            return $transaction;
        }

        $transaction['lines'] = $this->orderOrderLines->get($order);

        return $transaction;
    }
}
