<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\ProcessTransaction;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\Payment;

class SaveRemainingAmount implements ProcessTransactionInterface
{
    public function process(OrderInterface $order, ?MollieOrder $mollieOrder = null, ?Payment $molliePayment = null)
    {
        if ($mollieOrder) {
            $this->processOrder($order, $mollieOrder);
        }
    }

    private function processOrder(OrderInterface $order, MollieOrder $mollieOrder)
    {
        $amount = 0;
        foreach ($mollieOrder->_embedded->payments as $payment) {
            if (!isset($payment->details->remainderAmount)) {
                continue;
            }

            $amount += $payment->details->remainderAmount->value;
        }

        $order->getPayment()->setAdditionalInformation('remainder_amount', $amount);
    }
}
