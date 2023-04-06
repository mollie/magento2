<?php

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class DateOfBirth implements TransactionPartInterface
{
    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            return $transaction;
        }

        if ($order->getCustomerDob()) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $order->getCustomerDob());
            $transaction['consumerDateOfBirth'] = $date->format('Y-m-d');
        }

        return $transaction;
    }
}
