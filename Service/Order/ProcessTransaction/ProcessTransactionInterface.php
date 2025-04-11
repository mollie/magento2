<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\ProcessTransaction;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\Payment;

interface ProcessTransactionInterface
{
    /**
     * This function gets called on a response from Mollie. Only $mollieOrder or $molliePayment will be set, not both.
     *
     * @param OrderInterface $order
     * @param MollieOrder|null $mollieOrder
     * @param Payment|null $molliePayment
     * @return void
     */
    public function process(OrderInterface $order, ?MollieOrder $mollieOrder = null, ?Payment $molliePayment = null);
}
