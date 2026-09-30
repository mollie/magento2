<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Uncancel;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Sales\Model\Order\Item as OrderItem;

class LegacyStockDeduction
{
    public function __construct(
        private readonly StockConfigurationInterface $stockConfiguration,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly StockItemRepositoryInterface $stockItemRepository,
    ) {}

    public function execute(OrderItem $orderItem): void
    {
        if (!$this->wasReturnedToStockOnCancel($orderItem)) {
            return;
        }

        $stockItem = $this->stockRegistry->getStockItem(
            $orderItem->getProductId(),
            $this->stockConfiguration->getDefaultScopeId(),
        );

        if (!$this->canDeduct($stockItem, $orderItem)) {
            return;
        }

        $stockItem->setQty($stockItem->getQty() - $this->getQuantityToDeduct($orderItem));
        $this->stockItemRepository->save($stockItem);
    }

    private function wasReturnedToStockOnCancel(OrderItem $orderItem): bool
    {
        return $orderItem->getProductId() !== null &&
            $orderItem->getChildrenItems() === [] &&
            $this->getQuantityToDeduct($orderItem) > 0 &&
            $this->stockConfiguration->getCanBackInStock();
    }

    private function canDeduct(StockItemInterface $stockItem, OrderItem $orderItem): bool
    {
        return $stockItem->getItemId() !== null &&
            $stockItem->getManageStock() &&
            $this->stockConfiguration->canSubtractQty() &&
            $this->stockConfiguration->isQty($orderItem->getProductType());
    }

    private function getQuantityToDeduct(OrderItem $orderItem): float
    {
        return (float)$orderItem->getQtyOrdered() -
            max((float)$orderItem->getQtyShipped(), (float)$orderItem->getQtyInvoiced()) -
            (float)$orderItem->getQtyCanceled();
    }
}
