<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class ApplePayPaymentToken implements TransactionPartInterface
{
    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (!isset($additionalData['applepay_payment_token'])) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['applePayPaymentToken'] = $additionalData['applepay_payment_token'];
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['applePayPaymentToken'] = $additionalData['applepay_payment_token'];
        }

        return $transaction;
    }
}
