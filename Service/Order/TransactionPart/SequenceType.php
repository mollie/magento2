<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;

class SequenceType implements TransactionPartInterface
{
    /**
     * @var OrderContainsSubscriptionProduct
     */
    private $orderContainsSubscriptionProduct;

    public function __construct(
        OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct
    ) {
        $this->orderContainsSubscriptionProduct = $orderContainsSubscriptionProduct;
    }

    public function process(OrderInterface $order, $apiMethod, array $transaction): array
    {
        if (!$this->orderContainsSubscriptionProduct->check($order)) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['sequenceType'] = 'first';
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['sequenceType'] = 'first';
        }

        return $transaction;
    }
}
