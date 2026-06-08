<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Types\OrderLineType;
use Mollie\Payment\Helper\General;

class MagentoGiftCard implements GeneratorInterface
{
    public function __construct(
        private General $mollieHelper
    ) {}

    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$order->getData('base_gift_cards_amount')) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = $order->getData(($forceBaseCurrency ? 'base_' : '') . 'gift_cards_amount');

        if (abs($amount) < 0.01) {
            return $orderLines;
        }

        $orderLines[] = [
            'type' => OrderLineType::GIFT_CARD,
            'description' => __('Magento Gift Card'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'vatRate' => '0.00',
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, '0.00'),
        ];

        return $orderLines;
    }
}
