<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require 'Magento/Catalog/_files/product_simple.php';
$simpleProduct = $product;

require 'Magento/ConfigurableProduct/_files/configurable_products.php';
$configurableProduct = $product;

require 'Magento/Sales/_files/order.php';

/** @var \Magento\Catalog\Model\Product $product */
/** @var \Magento\Sales\Model\Order $order */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$orderItems = [
    [
        'product_id' => $simpleProduct->getEntityId(),
        'price' => 100,
        'base_price' => 100,
        'order_id' => $order->getId(),
        'row_total' => 200,
        'base_row_total' => 200,
        'product_type' => 'simple',
        'tax_percent' => 21,
        'qty_ordered' => 2,
    ]
];

/** @var array $orderItemData */
foreach ($orderItems as $orderItemData) {
    /** @var $orderItem \Magento\Sales\Model\Order\Item */
    $orderItem = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
        \Magento\Sales\Model\Order\Item::class
    );
    $orderItem
        ->setData($orderItemData)
        ->save();
}
