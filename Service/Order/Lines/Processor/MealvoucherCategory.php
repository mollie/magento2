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

class MealvoucherCategory implements ProcessorInterface
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

    public function process($orderLine, OrderInterface $order, OrderItemInterface $orderItem = null): array
    {
        if (!$orderItem || !$order->getPayment() || $order->getPayment()->getMethod() != 'mollie_methods_mealvoucher') {
            return $orderLine;
        }

        $category = $this->config->getMealvoucherCategory($order->getStoreId());
        if ($category == 'custom_attribute') {
            $orderLine['category'] = $this->getCustomAttribute($orderItem);

            return $orderLine;
        }

        $orderLine['category'] = $category;
        return $orderLine;
    }

    private function getCustomAttribute(OrderItemInterface $orderItem)
    {
        /** @var ProductInterface $product */
        $product = $orderItem->getProduct();

        return $product->getAttributeText($this->config->getMealvoucherCustomAttribute());
    }
}