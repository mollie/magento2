<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Processor;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

interface ProcessorInterface
{
    /**
     * @param array $orderLine
     * @param OrderInterface $order
     * @param OrderItemInterface|null $orderItem
     * @return array
     */
    public function process($orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array;
}
