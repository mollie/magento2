<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\ProcessTransaction;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;

class SaveRemainingAmount implements ProcessTransactionInterface
{
    public function process(OrderInterface $order, Payment $molliePayment): void
    {
        if (!isset($molliePayment->details->remainderAmount)) {
            return;
        }

        $order->getPayment()->setAdditionalInformation(
            'remainder_amount',
            $molliePayment->details->remainderAmount->value,
        );
    }
}
