<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use Magento\Tax\Model\Config as TaxConfig;
use Mollie\Payment\Model\ResourceModel\OrderLines\Collection as OrderLinesCollection;
use Mollie\Payment\Model\ResourceModel\OrderLines\CollectionFactory as OrderLinesCollectionFactory;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Payment\Service\Order\Lines\StoreCredit;

class OrderLines extends AbstractModel
{

    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var TaxConfig
     */
    private $taxConfig;
    /**
     * @var TaxItem
     */
    private $taxItem;
    /**
     * @var OrderLinesFactory
     */
    private $orderLinesFactory;
    /**
     * @var OrderLinesCollectionFactory
     */
    private $orderLinesCollection;
    /**
     * @var StoreCredit
     */
    private $storeCredit;

    /**
     * OrderLines constructor.
     *
     * @param MollieHelper                $mollieHelper
     * @param TaxConfig                   $taxConfig
     * @param TaxItem                     $taxItem
     * @param OrderLinesFactory           $orderLinesFactory
     * @param OrderLinesCollectionFactory $orderLinesCollection
     * @param Context                     $context
     * @param Registry                    $registry
     * @param StoreCredit                 $storeCredit
     * @param AbstractResource|null       $resource
     * @param AbstractDb|null             $resourceCollection
     * @param array                       $data
     */
    public function __construct(
        MollieHelper $mollieHelper,
        TaxConfig $taxConfig,
        TaxItem $taxItem,
        OrderLinesFactory $orderLinesFactory,
        OrderLinesCollectionFactory $orderLinesCollection,
        Context $context,
        Registry $registry,
        StoreCredit $storeCredit,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->taxConfig = $taxConfig;
        $this->taxItem = $taxItem;
        $this->orderLinesFactory = $orderLinesFactory;
        $this->orderLinesCollection = $orderLinesCollection;
        $this->storeCredit = $storeCredit;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get Order lines of Order
     *
     * @param Order $order
     *
     * @return array
     */
    public function getOrderLines(Order $order)
    {
        $forceBaseCurrency = $this->mollieHelper->useBaseCurrency($order->getStoreId());
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $orderLines = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $quantity = round($item->getQtyOrdered());
            $rowTotalInclTax = $forceBaseCurrency ? $item->getBaseRowTotalInclTax() : $item->getRowTotalInclTax();
            $discountAmount = $forceBaseCurrency ? $item->getBaseDiscountAmount() : $item->getDiscountAmount();

            /**
             * The price of a single item including VAT in the order line.
             * Calculated back from the $rowTotalInclTax to overcome rounding issues.
             */
            $unitPrice = round($rowTotalInclTax / $quantity, 2);

            /**
             * The total amount of the line, including VAT and discounts
             * Should Match: (unitPrice × quantity) - discountAmount
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $rowTotalInclTax - $discountAmount;

            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we calculate this instead of using $item->getTaxAmount() to overcome
             * any rouding issues.
             */
            $vatAmount = round($totalAmount * ($item->getTaxPercent() / (100 + $item->getTaxPercent())), 2);

            $orderLine = [
                'item_id'        => $item->getId(),
                'type'           => $item->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                'name'           => preg_replace("/[^A-Za-z0-9 -]/", "", $item->getName()),
                'quantity'       => $quantity,
                'unitPrice'      => $this->mollieHelper->getAmountArray($currency, $unitPrice),
                'totalAmount'    => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'vatRate'        => sprintf("%.2f", $item->getTaxPercent()),
                'vatAmount'      => $this->mollieHelper->getAmountArray($currency, $vatAmount),
                'sku'            => $item->getProduct()->getSku(),
                'productUrl'     => $item->getProduct()->getProductUrl()
            ];

            if ($discountAmount) {
                $orderLine['discountAmount'] = $this->mollieHelper->getAmountArray($currency, $discountAmount);
            }

            $orderLines[] = $orderLine;

            if ($item->getProductType() == ProductType::TYPE_BUNDLE) {
                /** @var Order\Item $childItem */
                foreach ($item->getChildrenItems() as $childItem) {
                    $orderLines[] = [
                        'item_id'        => $childItem->getId(),
                        'type'           => $childItem->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                        'name'           => preg_replace("/[^A-Za-z0-9 -]/", "", $childItem->getName()),
                        'quantity'       => $quantity,
                        'unitPrice'      => $this->mollieHelper->getAmountArray($currency, 0),
                        'totalAmount'    => $this->mollieHelper->getAmountArray($currency, 0),
                        'vatRate'        => sprintf("%.2f", $childItem->getTaxPercent()),
                        'vatAmount'      => $this->mollieHelper->getAmountArray($currency, 0),
                        'sku'            => $childItem->getProduct()->getSku(),
                        'productUrl'     => $childItem->getProduct()->getProductUrl()
                    ];
                }
            }
        }

        if (!$order->getIsVirtual()) {
            $baseShipping = $forceBaseCurrency ? $order->getBaseShippingAmount() : $order->getShippingAmount();
            $rowTotalInclTax = $forceBaseCurrency ? $baseShipping + $order->getBaseShippingTaxAmount() : $baseShipping + $order->getShippingTaxAmount();
            $discountAmount = $forceBaseCurrency ? $order->getBaseShippingDiscountAmount() : $order->getShippingDiscountAmount();

            /**
             * The total amount of the line, including VAT and discounts
             * Should Match: (unitPrice × quantity) - discountAmount
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $rowTotalInclTax - $discountAmount;

            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we calculate this instead of using $order->getBaseShippingTaxAmount() to overcome
             * any rouding issues.
             */
            $vatRate = $this->getShippingVatRate($order);
            $vatAmount = round($totalAmount * ($vatRate / (100 + $vatRate)), 2);

            $orderLine = [
                'item_id'        => '',
                'type'           => 'shipping_fee',
                'name'           => preg_replace("/[^A-Za-z0-9 -]/", "", $order->getShippingDescription()),
                'quantity'       => 1,
                'unitPrice'      => $this->mollieHelper->getAmountArray($currency, $rowTotalInclTax),
                'totalAmount'    => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'vatRate'        => sprintf("%.2f", $vatRate),
                'vatAmount'      => $this->mollieHelper->getAmountArray($currency, $vatAmount),
                'sku'            => $order->getShippingMethod()
            ];

            if ($discountAmount) {
                $orderLine['discountAmount'] = $this->mollieHelper->getAmountArray($currency, $discountAmount);
            }

            $orderLines[] = $orderLine;
        }

        if ($this->storeCredit->orderHasStoreCredit($order)) {
            $orderLines[] = $this->storeCredit->getOrderLine($order, $forceBaseCurrency);
        }

        $this->saveOrderLines($orderLines, $order);
        foreach ($orderLines as &$orderLine) {
            unset($orderLine['item_id']);
        }

        return $orderLines;
    }

    /**
     * @param Order $order
     *
     * @return mixed
     */
    public function getShippingVatRate(Order $order)
    {
        $taxPercentage = '0.00';
        $taxItems = $this->taxItem->getTaxItemsByOrderId($order->getId());
        if (is_array($taxItems)) {
            $key = array_search('shipping', array_column($taxItems, 'taxable_item_type'));
            if ($key !== false && isset($taxItems[$key]['tax_percent'])) {
                $taxPercentage = $taxItems[$key]['tax_percent'];
            }
        }

        return $taxPercentage;
    }

    /**
     * @param Order $order
     * @param       $orderLines
     */
    public function saveOrderLines($orderLines, Order $order)
    {
        foreach ($orderLines as $line) {
            /** @var OrderLines $orderLine */
            $orderLine = $this->orderLinesFactory->create();
            $orderLine->addData($line)->setOrderId($order->getId())->save();
        }
    }

    /**
     * @param       $orderLines
     * @param Order $order
     *
     * @throws LocalizedException
     */
    public function linkOrderLines($orderLines, Order $order)
    {
        $key = 0;
        $orderLinesCollection = $this->getOrderLinesByOrderId($order->getId());

        /** @var OrderLines $orderLineRow */
        foreach ($orderLinesCollection as $orderLineRow) {
            if (!isset($orderLines[$key])) {
                throw new LocalizedException(__('Could not save Order Lines. Error: order line not found'));
            }

            if ($orderLines[$key]->sku != trim($orderLineRow->getSku())) {
                throw new LocalizedException(__('Could not save Order Lines. Error: sku\'s do not match'));
            }

            $orderLineRow->setLineId($orderLines[$key]->id)->save();
            $key++;
        }
    }

    /**
     * @param $orderId
     *
     * @return OrderLinesCollection
     */
    public function getOrderLinesByOrderId($orderId)
    {
        return $this->orderLinesCollection->create()->addFieldToFilter('order_id', ['eq' => $orderId]);
    }

    /**
     * @param      $orderLines
     * @param bool $paid
     */
    public function updateOrderLinesByWebhook($orderLines, $paid = false)
    {
        foreach ($orderLines as $line) {
            $orderLineRow = $this->getOrderLineByLineId($line->id);

            if ($paid) {
                $orderLineRow->setQtyPaid($line->quantity);
            }

            $orderLineRow->setQtyShipped($line->quantityShipped)
                ->setQtyCanceled($line->quantityCanceled)
                ->setQtyRefunded($line->quantityRefunded)
                ->save();
        }
    }

    /**
     * @param $lineId
     *
     * @return OrderLines
     */
    public function getOrderLineByLineId($lineId)
    {
        return $this->orderLinesFactory->create()->load($lineId, 'line_id');
    }

    /**
     * @param Order\Shipment $shipment
     */
    public function shipAllOrderLines($shipment)
    {
        $orderId = $shipment->getOrderId();
        $orderLinesCollection = $this->getOrderLinesByOrderId($orderId);
        foreach ($orderLinesCollection as $orderLineRow) {
            $qtyOrdered = $orderLineRow->getQtyOrdered();
            $orderLineRow->setQtyShipped($qtyOrdered)->save();
        }
    }

    /**
     * @param Order\Shipment $shipment
     *
     * @return array
     */
    public function getShipmentOrderLines($shipment)
    {
        $orderLines = [];

        /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
        foreach ($shipment->getItemsCollection() as $item) {
            if (!$item->getQty()) {
                continue;
            }
            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            $orderLines[] = ['id' => $lineId, 'quantity' => $item->getQty()];
        }

        return ['lines' => $orderLines];
    }

    /**
     * @param $itemId
     *
     * @return OrderLines
     */
    public function getOrderLineByItemId($itemId)
    {
        $orderLine = $this->orderLinesCollection->create()
            ->addFieldToFilter('item_id', ['eq' => $itemId])
            ->addFieldToFilter('line_id', ['notnull' => true])
            ->getLastItem();

        return $orderLine;
    }

    /**
     * @param Order\Creditmemo $creditmemo
     * @param                  $addShipping
     *
     * @return array
     */
    public function getCreditmemoOrderLines($creditmemo, $addShipping)
    {
        $orderLines = [];

        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            if ($lineId) {
                $orderLines[] = ['id' => $lineId, 'quantity' => round($item->getQty())];
            }
        }

        if ($addShipping) {
            $orderId = $creditmemo->getOrderId();
            $shippingFeeItemLine = $this->getShippingFeeItemLineOrder($orderId);
            $orderLines[] = ['id' => $shippingFeeItemLine->getLineId(), 'quantity' => 1];
        }

        return ['lines' => $orderLines];
    }

    /**
     * @param $orderId
     *
     * @return OrderLines
     */
    public function getShippingFeeItemLineOrder($orderId)
    {
        $shippingLine = $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->addFieldToFilter('type', ['eq' => 'shipping_fee'])
            ->getLastItem();

        return $shippingLine;
    }

    /**
     * @param $orderId
     *
     * @return int
     */
    public function getOpenForShipmentQty($orderId)
    {
        $qty = 0;
        $orderLinesCollection = $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->addFieldToFilter('type', ['eq' => 'physical'])
            ->addExpressionFieldToSelect(
                'open',
                'SUM(qty_ordered - qty_shipped - qty_refunded)',
                ['qty_ordered', 'qty_shipped', 'qty_refunded']
            );
        $orderLinesCollection->getSelect()->group('order_id');

        foreach ($orderLinesCollection as $orderLineRow) {
            if ($orderLineRow->getOpen() > 0) {
                $qty += $orderLineRow->getOpen();
            }
        }

        return $qty;
    }

    /**
     * @param $orderId
     *
     * @return int
     */
    public function getOpenForRefundQty($orderId)
    {
        $qty = 0;
        $orderLinesCollection = $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->addFieldToFilter('type', ['in' => ['physical', 'digital']])
            ->addExpressionFieldToSelect(
                'open',
                'SUM(qty_ordered - qty_refunded)',
                ['qty_ordered', 'qty_refunded']
            );
        $orderLinesCollection->getSelect()->group('order_id');

        foreach ($orderLinesCollection as $orderLineRow) {
            if ($orderLineRow->getOpen() > 0) {
                $qty += $orderLineRow->getOpen();
            }
        }

        return $qty;
    }

    /**
     *
     */
    public function _construct()
    {
        $this->_init('Mollie\Payment\Model\ResourceModel\OrderLines');
    }
}
