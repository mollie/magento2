<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\Generator\GeneratorInterface;

class OrderLinesGenerator
{
    /**
     * @var array
     */
    private $generators;

    /**
     * @param GeneratorInterface[] $generators
     */
    public function __construct(array $generators = [])
    {
        $this->generators = $generators;
    }

    public function execute(OrderInterface $order, array $orderLines): array
    {
        foreach ($this->generators as $generator) {
            $orderLines = $generator->process($order, $orderLines);
        }

        return $orderLines;
    }
}
