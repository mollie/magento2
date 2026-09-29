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
    public function process(OrderInterface $order, array $transaction): array
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (!array_key_exists('limited_methods', $additionalData) || !$additionalData['limited_methods']) {
            return $transaction;
        }

        $transaction['method'] = $this->mapMethods($additionalData['limited_methods']);

        return $transaction;
    }

    /**
     * The Mollie API only accepts Google Pay through the creditcard method.
     *
     * @param string|string[] $methods
     * @return string|string[]
     */
    private function mapMethods($methods)
    {
        if (!is_array($methods)) {
            return $methods === 'googlepay' ? 'creditcard' : $methods;
        }

        $methods = array_map(function ($method) {
            return $method === 'googlepay' ? 'creditcard' : $method;
        }, $methods);

        return array_values(array_unique($methods));
    }
}
