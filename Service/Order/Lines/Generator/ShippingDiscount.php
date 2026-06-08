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

class ShippingDiscount implements GeneratorInterface
{
    public function __construct(
        private General $mollieHelper
    ) {}

    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$order->getShippingDiscountAmount()) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = abs((float)$order->getData(($forceBaseCurrency ? 'base_' : '') . 'shipping_discount_amount'));

        if (abs($amount) < 0.01) {
            return $orderLines;
        }

        $orderLines[] = [
            'type' => OrderLineType::DISCOUNT,
            'description' => __('Shipping Discount'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'vatRate' => '0.00',
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, '0.00'),
        ];

        return $orderLines;
    }
}
