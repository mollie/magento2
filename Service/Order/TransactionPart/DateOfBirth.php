<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class DateOfBirth implements TransactionPartInterface
{
    public function process(OrderInterface $order, array $transaction): array
    {
        if (!$order->getPayment() || $order->getPayment()->getMethod() != 'mollie_methods_in3') {
            return $transaction;
        }

        if (!$order->getCustomerDob()) {
            return $transaction;
        }

        $date = DateTime::createFromFormat('Y-m-d', $order->getCustomerDob());
        if ($date) {
            $transaction['consumerDateOfBirth'] = $date->format('Y-m-d');
        }

        $date = DateTime::createFromFormat('Y-m-d 00:00:00', $order->getCustomerDob());
        if ($date) {
            $transaction['consumerDateOfBirth'] = $date->format('Y-m-d');
        }

        return $transaction;
    }
}
