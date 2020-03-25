<?php

namespace Mollie\Payment\Observer;

use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class SalesOrderUnCancelAfterCommit
 * @ref \Magento\CatalogInventory\Observer\ReindexQuoteInventoryObserver
 */
class ReindexProduct implements ObserverInterface
{
    /**
     * @var Processor
     */
    protected $stockIndexerProcessor;

    /**
     * @param Processor $stockIndexerProcessor
     */
    public function __construct(
        Processor $stockIndexerProcessor
    ) {
        $this->stockIndexerProcessor = $stockIndexerProcessor;
    }

    /**
     * Refresh stock index for specific stock items after successful order placement
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        $ids = [];
        foreach ($order->getAllItems() as $item) {
            /** @var OrderItemInterface $item */
            $ids[$item->getProductId()] = $item->getProductId();
            $ids += $this->addChildItems($item);
        }

        if ($ids) {
            $this->stockIndexerProcessor->reindexList($ids);
        }
    }

    /**
     * @param OrderItemInterface $item
     * @return array
     */
    private function addChildItems(OrderItemInterface $item)
    {
        $children = $item->getChildrenItems();
        if (!$children) {
            return [];
        }

        $ids = [];
        /** @var OrderItemInterface $childItem */
        foreach ($children as $childItem) {
            $ids[$childItem->getProductId()] = $childItem->getProductId();
        }

        return $ids;
    }
}
