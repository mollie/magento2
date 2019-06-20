<?php

namespace Mollie\Payment\Operation;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Helper\General;
use Magento\CatalogInventory\Api\RegisterProductSaleInterface;
use Mollie\Payment\Operation\UnCancelOrderOperation\ProductQty;

/**
 * Class UnCancelOrderOperation
 */
class UnCancelOrderOperation
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderManagement;

    /**
     * @var \Mollie\Payment\Helper\General
     */
    private $generalHelper;

    /**
     * @var \Magento\CatalogInventory\Api\RegisterProductSaleInterface
     */
    private $registerProductSale;

    /**
     * @var StockManagementInterface|\Magento\CatalogInventory\Model\StockManagement
     */
    private $stockManagement;

    /**
     * @var \Mollie\Payment\Operation\UnCancelOrderOperation\ProductQty
     */
    private $productQty;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * UnCancelOrderOperation constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface                   $eventManager
     * @param \Magento\Sales\Api\OrderRepositoryInterface                 $orderManagement
     * @param \Magento\CatalogInventory\Api\StockManagementInterface      $stockManagement
     * @param \Mollie\Payment\Operation\UnCancelOrderOperation\ProductQty $productQty
     * @param \Mollie\Payment\Helper\General                              $generalHelper
     * @param \Magento\Framework\App\ResourceConnection                   $resourceConnection
     * RegisterProductSaleInterface is deprecated in 2.3.0 but we use it to allow backward compatibility
     */
    public function __construct(
        ManagerInterface $eventManager,
        OrderRepositoryInterface $orderManagement,
        StockManagementInterface $stockManagement,
        ProductQty $productQty,
        General $generalHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->orderManagement = $orderManagement;
        $this->eventManager = $eventManager;
        $this->generalHelper = $generalHelper;
        $this->stockManagement = $stockManagement;
        $this->productQty = $productQty;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string                     $comment
     * @param bool                       $graceful
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     * @throws \Throwable
     * Credits: https://github.com/Genmato/M2_UnCancelOrder/blob/master/Model/Order.php
     */
    public function execute($order, $comment = '', $graceful = true)
    {
        if (!$order->isCanceled() && $graceful) {
            return $order;
        }

        if (!$order->isCanceled() && !$graceful) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot un-cancel this order.'));
        }

        $connection = $this->resourceConnection->getConnection('sales');
        $connection->beginTransaction();

        $state = Order::STATE_PROCESSING;
        $productStockQty = [];

        foreach ($order->getAllVisibleItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if (!isset($productStockQty[$item->getProductId()])) {
                $productStockQty[$item->getProductId()] = 0;
            }
            $productStockQty[$item->getProductId()] += $item->getQtyCanceled();
            foreach ($item->getChildrenItems() as $child) {
                /** @var \Magento\Sales\Model\Order\Item $child */
                if (!isset($productStockQty[$item->getProductId()])) {
                    $productStockQty[$child->getProductId()] = 0;
                }
                $productStockQty[$child->getProductId()] += $item->getQtyCanceled();
                $child->setQtyCanceled(0);
                $child->setTaxCanceled(0);
                $child->setDiscountTaxCompensationCanceled(0);
            }
            $item->setQtyCanceled(0);
            $item->setTaxCanceled(0);
            $item->setDiscountTaxCompensationCanceled(0);
            $this->eventManager->dispatch('sales_order_item_uncancel', ['item' => $item]);
        }

        /** @ref Magento\CatalogInventory\Observer\SubtractQuoteInventoryObserver */
        $items = $this->productQty->getProductQty($order->getAllItems());
        $this->registerProductSale->registerProductsSale($productStockQty);
        $this->stockManagement->registerProductsSale(
            $items,
            $order->getStore()->getWebsiteId()
        );

        $order->setSubtotalCanceled(0);
        $order->setBaseSubtotalCanceled(0);
        $order->setTaxCanceled(0);
        $order->setBaseTaxCanceled(0);
        $order->setShippingCanceled(0);
        $order->setBaseShippingCanceled(0);
        $order->setDiscountCanceled(0);
        $order->setBaseDiscountCanceled(0);
        $order->setTotalCanceled(0);
        $order->setBaseTotalCanceled(0);
        $order->setState($state);
        $order->setStatus($this->generalHelper->getStatusPending($order->getStoreId()));

        if (!empty($comment)) {
            $order->addStatusHistoryComment($comment, false);
        }

        $connection->beginTransaction();

        try {
            $this->orderManagement->save($order);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->eventManager->dispatch('sales_order_uncancel_after_commit', ['order' => $order]);

        return $order;
    }
}
