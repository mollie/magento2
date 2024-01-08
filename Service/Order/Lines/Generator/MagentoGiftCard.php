<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Types\OrderLineType;
use Mollie\Payment\Helper\General;

class MagentoGiftCard implements GeneratorInterface
{
    /**
     * @var General
     */
    private $mollieHelper;

    public function __construct(
        General $mollieHelper
    ) {
        $this->mollieHelper = $mollieHelper;
    }

    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$order->getData('base_gift_cards_amount')) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = $order->getData(($forceBaseCurrency ? 'base_' : '') . 'gift_cards_amount');

        if (abs($amount) < 0.01) {
            return $orderLines;
        }

        $orderLines[] = [
            'type' => OrderLineType::TYPE_GIFT_CARD,
            'name' => __('Magento Gift Card'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'vatRate' => '0.00',
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, '0.00'),
        ];

        return $orderLines;
    }
}
