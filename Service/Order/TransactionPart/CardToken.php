<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CardToken implements TransactionPartInterface
{
    /**
     * @param OrderInterface $order
     * @param $apiMethod
     * @param array $transaction
     * @return array
     */
    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if ($order->getPayment()->getMethod() != 'mollie_methods_creditcard' || !isset($additionalData['card_token'])) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['cardToken'] = $additionalData['card_token'];
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['cardToken'] = $additionalData['card_token'];
        }

        return $transaction;
    }
}
