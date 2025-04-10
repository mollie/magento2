<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Processor;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Helper\General;

class BundleWithoutDynamicPricing implements ProcessorInterface
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

    public function process($orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array
    {
        if (
            !$orderItem ||
            $orderItem->getProductType() !== Type::TYPE_BUNDLE ||
            !$orderItem->getProduct() ||
            $orderItem->getProduct()->getPriceType() != Price::PRICE_TYPE_FIXED
        ) {
            return $orderLine;
        }

        $forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $discountAmount = $this->getDiscountAmountWithTax($orderItem, $forceBaseCurrency);
        if (!$discountAmount) {
            return $orderLine;
        }

        // Magento provides us with a discount amount without tax, but calculates with tax in this case. So recalculate
        // the unit price, total amount and vat amount.

        $taxPercent = $orderItem->getTaxPercent();
        $unitPrice = $orderLine['totalAmount']['value'] / $orderItem->getQtyOrdered();
        $quantity = (float) $orderItem->getQtyOrdered();
        $newVatAmount = ((($quantity * $unitPrice) - $discountAmount) / (100 + $taxPercent)) * $taxPercent;

        $orderLine['unitPrice'] = $this->mollieHelper->getAmountArray($currency, $unitPrice);
        $orderLine['totalAmount'] = $this->mollieHelper->getAmountArray(
            $currency,
            ($quantity * $unitPrice) - $discountAmount
        );
        $orderLine['vatAmount'] = $this->mollieHelper->getAmountArray($currency, $newVatAmount);
        $orderLine['discountAmount'] = $this->mollieHelper->getAmountArray($currency, $discountAmount);

        return $orderLine;
    }

    private function getDiscountAmountWithTax(OrderItemInterface $item, bool $forceBaseCurrency)
    {
        if ($forceBaseCurrency) {
            return abs($item->getBaseDiscountAmount());
        }

        return abs($item->getDiscountAmount());
    }
}
