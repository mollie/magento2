<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Config;

class LimonetikCategory
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

    public function addToOrderLine(OrderInterface $order, OrderItemInterface $orderItem, $orderLine)
    {
        $category = $this->config->getLimonetikCategory($order->getStoreId());
        if ($category == 'custom_attribute') {
            $orderLine['category'] = $this->getCustomAttribute($orderItem, $orderLine);
            return $orderLine;
        }

        $orderLine['category'] = $category;
        return $orderLine;
    }

    private function getCustomAttribute(OrderItemInterface $orderItem, $orderLine)
    {
        /** @var ProductInterface $product */
        $product = $orderItem->getProduct();

        return $product->getData($this->config->getLimonetikCustomAttribute());
    }
}