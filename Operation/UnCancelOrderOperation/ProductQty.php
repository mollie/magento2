<?php

namespace Mollie\Payment\Operation\UnCancelOrderOperation;

use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Class ProductQty
 * @ref \Magento\CatalogInventory\Observer\ProductQty
 */
class ProductQty
{
    /**
     * Prepare array with information about used product qty and product stock item
     *
     * @param array $relatedItems
     * @return array
     */
    public function getProductQty($relatedItems)
    {
        $items = [];
        foreach ($relatedItems as $item) {
            $productId = $item->getProductId();
            if (!$productId) {
                continue;
            }
            $children = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    $this->addItemToQtyArray($childItem, $items);
                }
            } else {
                $this->addItemToQtyArray($item, $items);
            }
        }
        return $items;
    }

    /**
     * Adds stock item qty to $items (creates new entry or increments existing one)
     *
     * @param OrderItem $quoteItem
     * @param array &$items
     * @return void
     */
    protected function addItemToQtyArray(OrderItem $quoteItem, &$items)
    {
        $productId = $quoteItem->getProductId();
        if (!$productId) {
            return;
        }
        if (isset($items[$productId])) {
            $items[$productId] += $quoteItem->getQtyCanceled();
        } else {
            $items[$productId] = $quoteItem->getQtyCanceled();
        }
    }
}
