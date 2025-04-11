<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Service\Order\Lines\Processor\ProcessorInterface;

class OrderLinesProcessor
{
    /**
     * @var ProcessorInterface
     */
    private $processors;

    public function __construct(
        $processors = []
    ) {
        $this->processors = $processors;
    }

    public function process(array $orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array
    {
        foreach ($this->processors as $processor) {
            $orderLine = $processor->process($orderLine, $order, $orderItem);
        }

        return $orderLine;
    }
}
