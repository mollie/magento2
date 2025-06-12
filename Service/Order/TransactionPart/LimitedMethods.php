<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class LimitedMethods implements TransactionPartInterface
{
    public function process(OrderInterface $order, $apiMethod, array $transaction): array
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (!array_key_exists('limited_methods', $additionalData) || !$additionalData['limited_methods']) {
            return $transaction;
        }

        $transaction['method'] = $additionalData['limited_methods'];

        return $transaction;
    }
}
