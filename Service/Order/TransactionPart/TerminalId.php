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

class TerminalId implements TransactionPartInterface
{
    /**
     * @inheritDoc
     */
    public function process(OrderInterface $order, $apiMethod, array $transaction): array
    {
        if ($order->getPayment()->getMethod() != 'mollie_methods_pointofsale') {
            return $transaction;
        }

        $value = $this->getSelectedTerminal($order);
        if ($value && $apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['terminalId'] = $value;
        }

        if ($value && $apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['terminalId'] = $value;
        }

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
