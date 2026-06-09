<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class SequenceType implements TransactionPartInterface
{
    public function __construct(
        private OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct,
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        if ($this->orderContainsSubscriptionProduct->check($order)) {
            $transaction['sequenceType'] = 'first';
            return $transaction;
        }

        $payment = $order->getPayment();
        if (!$payment) {
            return $transaction;
        }

        $info = $payment->getAdditionalInformation();

        if (($info['mollie_mandate_id'] ?? '') !== '') {
            $transaction['sequenceType'] = 'oneoff';
            return $transaction;
        }

        return $transaction;
    }
}
