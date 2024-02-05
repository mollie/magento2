<?php

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Types\OrderLineType;
use Mollie\Payment\Helper\General;

class ShippingDiscount implements GeneratorInterface
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
        if (!$order->getShippingDiscountAmount()) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = abs($order->getData(($forceBaseCurrency ? 'base_' : '') . 'shipping_discount_amount'));

        if (abs($amount) < 0.01) {
            return $orderLines;
        }

        $orderLines[] = [
            'type' => OrderLineType::TYPE_DISCOUNT,
            'name' => __('Shipping Discount'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$amount),
            'vatRate' => '0.00',
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, '0.00'),
        ];

        return $orderLines;
    }
}
