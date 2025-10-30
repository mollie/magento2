<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderLinesProcessor
{
    public function __construct(
        private array $processors = [],
    ) {
    }

    public function process(array $orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array
    {
        foreach ($this->processors as $processor) {
            $orderLine = $processor->process($orderLine, $order, $orderItem);
        }

        return $orderLine;
    }
}
