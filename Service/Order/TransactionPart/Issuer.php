<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class Issuer implements TransactionPartInterface
{
    public function process(OrderInterface $order, array $transaction): array
    {
        if ($value = $this->getSelectedIssuer($order)) {
            $transaction['issuer'] = $value;
        }

        return $transaction;
    }

    private function getSelectedIssuer(OrderInterface $order): ?string
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (isset($additionalData['selected_issuer'])) {
            return $additionalData['selected_issuer'];
        }

        return null;
    }
}
