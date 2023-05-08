<?php

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class DateOfBirth implements TransactionPartInterface
{
    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if (!$order->getPayment() || $order->getPayment()->getMethod() != 'mollie_methods_in3') {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            return $transaction;
        }

        if (!$order->getCustomerDob()) {
            return $transaction;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $order->getCustomerDob());
        if ($date) {
            $transaction['consumerDateOfBirth'] = $date->format('Y-m-d');
        }

        $date = \DateTime::createFromFormat('Y-m-d 00:00:00', $order->getCustomerDob());
        if ($date) {
            $transaction['consumerDateOfBirth'] = $date->format('Y-m-d');
        }

        return $transaction;
    }
}
