<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Order\ProcessTransaction\ProcessTransactionInterface;

class TransactionProcessor
{
    /**
     * @var array
     */
    private $processors;

    public function __construct(
        array $processors = []
    ) {
        $this->processors = $processors;
    }

    public function process(OrderInterface $order, ?MollieOrder $mollieOrder = null, ?Payment $molliePayment = null)
    {
        /** @var ProcessTransactionInterface $processor */
        foreach ($this->processors as $processor) {
            $processor->process($order, $mollieOrder, $molliePayment);
        }
    }
}
