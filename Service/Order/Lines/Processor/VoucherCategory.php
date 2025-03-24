<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Processor;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Config;

class VoucherCategory implements ProcessorInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function process($orderLine, OrderInterface $order, ?OrderItemInterface $orderItem = null): array
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
        $category = $this->config->getVoucherCategory($order->getStoreId());
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
