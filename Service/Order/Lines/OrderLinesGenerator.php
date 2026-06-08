<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\Generator\GeneratorInterface;

class OrderLinesGenerator
{
    /**
     * @param GeneratorInterface[] $generators
     */
    public function __construct(
        private array $generators = []
    ) {}

    public function execute(OrderInterface $order, array $orderLines): array
    {
        foreach ($this->generators as $generator) {
            $orderLines = $generator->process($order, $orderLines);
        }

        return $orderLines;
    }
}
