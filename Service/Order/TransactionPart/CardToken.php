<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CardToken implements TransactionPartInterface
{
    public function process(OrderInterface $order, array $transaction): array
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if ($order->getPayment()->getMethod() !== 'mollie_methods_creditcard') {
            return $transaction;
        }

        // When using a saved mandate, MandateId.php populates the request; do not also send cardToken.
        if (($additionalData['mollie_mandate_id'] ?? '') !== '') {
            return $transaction;
        }

        if (!isset($additionalData['card_token'])) {
            return $transaction;
        }

        $additional = $transaction['additional'] ?? [];
        $additional['cardToken'] = $additionalData['card_token'];
        $transaction['additional'] = $additional;

        return $transaction;
    }
}
