<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class ApplePayPaymentToken implements TransactionPartInterface
{
    public function process(OrderInterface $order, array $transaction): array
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (!isset($additionalData['applepay_payment_token'])) {
            return $transaction;
        }

        $transaction['applePayPaymentToken'] = $additionalData['applepay_payment_token'];

        return $transaction;
    }
}
