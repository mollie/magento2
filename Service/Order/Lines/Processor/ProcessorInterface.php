<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
    public function process(array $orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array;
}
