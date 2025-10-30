<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class TerminalId implements TransactionPartInterface
{
    public function process(OrderInterface $order, array $transaction): array
    {
        if ($order->getPayment()->getMethod() != 'mollie_methods_pointofsale') {
            return $transaction;
        }

        $value = $this->getSelectedTerminal($order);
        if (!$value) {
            return $transaction;
        }

        $additional = $transaction['additional'] ?? [];
        $additional['terminalId'] = $value;
        $transaction['additional'] = $additional;

        return $transaction;
    }

    private function getSelectedTerminal(OrderInterface $order): ?string
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (isset($additionalData['selected_terminal'])) {
            return $additionalData['selected_terminal'];
        }

        return null;
    }
}
