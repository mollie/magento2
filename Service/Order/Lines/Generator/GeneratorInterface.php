<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;

interface GeneratorInterface
{
    public function process(OrderInterface $order, array $orderLines): array;
}
