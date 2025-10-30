<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines\Processor;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Config;

class VoucherCategory implements ProcessorInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function process(array $orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array
    {
        if (!$orderItem || !$order->getPayment() || $order->getPayment()->getMethod() != 'mollie_methods_voucher') {
            return $orderLine;
        }

        $category = $this->getCategoryValue($order, $orderItem);
        if ($category !== null) {
            $orderLine['category'] = strtolower($category);
        }

        return $orderLine;
    }

    private function getCategoryValue(OrderInterface $order, OrderItemInterface $orderItem)
    {
        $category = $this->config->getVoucherCategory(storeId($order->getStoreId()));
        if ($category != 'custom_attribute') {
            return $category;
        }

        $value = $this->getCustomAttribute($orderItem);
        if ($value == 'none') {
            return null;
        }

        return $value;
    }

    private function getCustomAttribute(OrderItemInterface $orderItem)
    {
        /** @var ProductInterface $product */
        $product = $orderItem->getProduct();

        $attributeCode = $this->config->getVoucherCustomAttribute();
        $value = $product->getAttributeText($attributeCode);
        if ($value) {
            return $value;
        }

        return $product->getData($attributeCode);
    }
}
