<?php

namespace Mollie\Payment\Observer;

use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesOrderUnCancelAfterCommit
 * @ref \Magento\CatalogInventory\Observer\ReindexQuoteInventoryObserver
 */
class SalesOrderUnCancelAfterCommit implements ObserverInterface
{
    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected $stockIndexerProcessor;

    /**
     * @var \Magento\CatalogInventory\Observer\ItemsForReindex
     */
    protected $itemsForReindex;

    /**
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor
     * @param ItemsForReindex $itemsForReindex
     */
    public function __construct(
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        ItemsForReindex $itemsForReindex
    ) {
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->itemsForReindex = $itemsForReindex;
    }

    /**
     * Refresh stock index for specific stock items after successful order placement
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        $productIds = [];
        foreach ($order->getAllItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $productIds[$item->getProductId()] = $item->getProductId();
            $children = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    /** @var \Magento\Sales\Model\Order\Item $childItem */
                    $productIds[$childItem->getProductId()] = $childItem->getProductId();
                }
            }
        }

        if ($productIds) {
            $this->stockIndexerProcessor->reindexList($productIds);
        }
    }
}
