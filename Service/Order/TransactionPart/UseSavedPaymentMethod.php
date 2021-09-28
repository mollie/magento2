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

class UseSavedPaymentMethod implements TransactionPartInterface
{
    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if (
            !$order->getPayment() ||
            !$order->getPayment()->getExtensionAttributes() ||
            !$order->getPayment()->getExtensionAttributes()->getVaultPaymentToken()
        ) {
            return $transaction;
        }

        $paymentToken = $order->getPayment()->getExtensionAttributes()->getVaultPaymentToken();
        if ($order->getPayment()->getMethod() != $paymentToken->getPaymentMethodCode()) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['sequenceType'] = 'recurring';
            $transaction['mandateId'] = $paymentToken->getGatewayToken();
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['sequenceType'] = 'recurring';
            $transaction['payment']['mandateId'] = $paymentToken->getGatewayToken();
        }

        return $transaction;
    }
}
