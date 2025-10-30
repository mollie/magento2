<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General;

class AheadworksAddFreeGift implements GeneratorInterface
{
    public function __construct(
        private General $mollieHelper
    ) {}

    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$this->hasAheadworksFreeGiftItems($order)) {
            return $orderLines;
        }

        $discount = 0;
        foreach ($order->getItems() as $item) {
            $discount += abs($item->getAwAfptcAmount());
        }

        if (!$discount) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $orderLines[] = [
            'type' => 'surcharge',
            'description' => 'Aheadworks Add Free Gift',
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$discount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$discount),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, 0.0),
        ];

        return $orderLines;
    }

    private function hasAheadworksFreeGiftItems(OrderInterface $order): bool
    {
        foreach ($order->getItems() as $item) {
            if ($item->getAwAfptcAmount() !== null) {
                return true;
            }
        }

        return false;
    }
}
