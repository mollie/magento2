<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require 'Magento/Catalog/_files/product_simple.php';
$simpleProduct = $product;

require 'Magento/ConfigurableProduct/_files/configurable_products.php';
$configurableProduct = $product;

require 'Magento/Sales/_files/order.php';

/** @var Product $product */
/** @var Order $order */
$objectManager = Bootstrap::getObjectManager();

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
    ],
];

/** @var array $orderItemData */
foreach ($orderItems as $orderItemData) {
    /** @var $orderItem Item */
    $orderItem = Bootstrap::getObjectManager()->create(
        Item::class,
    );
    $orderItem
        ->setData($orderItemData)
        ->save();
}
