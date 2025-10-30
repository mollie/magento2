<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;

interface TransactionPartInterface
{
    public function process(OrderInterface $order, array $transaction): array;
}
