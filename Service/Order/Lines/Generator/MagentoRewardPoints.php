<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);


namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General;

class MagentoRewardPoints implements GeneratorInterface
{
    public function __construct(
        private General $mollieHelper
    ) {}

    public function process(OrderInterface $order, array $orderLines): array
    {
        $extensionAttributes = $order->getExtensionAttributes();

        if (!method_exists($extensionAttributes, 'getRewardCurrencyAmount')) {
            return $orderLines;
        }

        $amount = $extensionAttributes->getRewardCurrencyAmount();
        if ($amount === null) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        if ($forceBaseCurrency) {
            $amount = $extensionAttributes->getBaseRewardCurrencyAmount();
        }

        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $orderLines[] = [
            'type' => 'surcharge',
            'description' => 'Reward Points',
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, 0.0),
        ];

        return $orderLines;
    }
}
