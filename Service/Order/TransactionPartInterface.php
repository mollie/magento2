<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;

interface TransactionPartInterface
{
    /**
     * @param OrderInterface $order
     * @param string $apiMethod
     * @param array $transaction
     * @return array
     */
    public function process(OrderInterface $order, $apiMethod, array $transaction);
}
