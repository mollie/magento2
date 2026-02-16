<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class SequenceType implements TransactionPartInterface
{
    public function __construct(
        private OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct,
        private Session $customerSession
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        if (!$this->shouldAddSequenceType($order)) {
            return $transaction;
        }

        $transaction['sequenceType'] = 'first';

        return $transaction;
    }

    private function shouldAddSequenceType(OrderInterface $order): bool
    {
        if ($this->orderContainsSubscriptionProduct->check($order)) {
            return true;
        }

        if (!$order->getPayment() || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        return false;
    }
}
