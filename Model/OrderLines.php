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
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Mollie\Payment\Model\ResourceModel\OrderLines\Collection as OrderLinesCollection;
use Mollie\Payment\Model\ResourceModel\OrderLines\CollectionFactory as OrderLinesCollectionFactory;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Payment\Service\Order\Creditmemo as CreditmemoService;
use Mollie\Payment\Service\Order\Lines\PaymentFee;
use Mollie\Payment\Service\Order\Lines\StoreCredit;

class OrderLines extends AbstractModel
{

    /**
     * @var MollieHelper
     */
    private $mollieHelper;
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
     * @var PaymentFee
     */
    private $paymentFee;
    /**
     * @var CreditmemoService
     */
    private $creditmemoService;

    /**
     * OrderLines constructor.
     *
     * @param MollieHelper                $mollieHelper
     * @param OrderLinesFactory           $orderLinesFactory
     * @param OrderLinesCollectionFactory $orderLinesCollection
     * @param Context                     $context
     * @param Registry                    $registry
     * @param StoreCredit                 $storeCredit
     * @param PaymentFee                  $paymentFee
     * @param CreditmemoService           $creditmemoService
     * @param AbstractResource|null       $resource
     * @param AbstractDb|null             $resourceCollection
     * @param array                       $data
     */
    public function __construct(
        MollieHelper $mollieHelper,
        OrderLinesFactory $orderLinesFactory,
        OrderLinesCollectionFactory $orderLinesCollection,
        Context $context,
        Registry $registry,
        StoreCredit $storeCredit,
        PaymentFee $paymentFee,
        CreditmemoService $creditmemoService,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->orderLinesFactory = $orderLinesFactory;
        $this->orderLinesCollection = $orderLinesCollection;
        $this->storeCredit = $storeCredit;
        $this->paymentFee = $paymentFee;
        $this->creditmemoService = $creditmemoService;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get Order lines of Order
     *
     * @param OrderInterface $order
     *
     * @return array
     */
    public function getOrderLines(OrderInterface $order)
    {
        $forceBaseCurrency = $this->mollieHelper->useBaseCurrency($order->getStoreId());
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $orderLines = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {

            /**
             * The total amount of the line, including VAT and discounts
             * Should Match: (unitPrice × quantity) - discountAmount
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $this->getTotalAmountOrderItem($item, $forceBaseCurrency);

            /**
             * The total discount amount of the line.
             */
            $discountAmount = $this->getDiscountAmountOrderItem($item, $forceBaseCurrency);

            /**
             * The price of a single item including VAT in the order line.
             * Calculated back from the totalAmount + discountAmount to overcome rounding issues.
             */
            $unitPrice = round(($totalAmount + $discountAmount) / $item->getQtyOrdered(), 2);

            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we calculate this instead of using $item->getTaxAmount() to overcome
             * any rouding issues.
             */
            $vatAmount = round($totalAmount * ($item->getTaxPercent() / (100 + $item->getTaxPercent())), 2);

            $orderLine = [
                'item_id'     => $item->getId(),
                'type'        => $item->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $item->getName()),
                'quantity'    => round($item->getQtyOrdered()),
                'unitPrice'   => $this->mollieHelper->getAmountArray($currency, $unitPrice),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'vatRate'     => sprintf("%.2f", $item->getTaxPercent()),
                'vatAmount'   => $this->mollieHelper->getAmountArray($currency, $vatAmount),
                'sku'         => $item->getProduct()->getSku(),
                'productUrl'  => $item->getProduct()->getProductUrl()
            ];

            if ($discountAmount) {
                $orderLine['discountAmount'] = $this->mollieHelper->getAmountArray($currency, $discountAmount);
            }

            $orderLines[] = $orderLine;

            if ($item->getProductType() == ProductType::TYPE_BUNDLE) {
                /** @var Order\Item $childItem */
                foreach ($item->getChildrenItems() as $childItem) {
                    $orderLines[] = [
                        'item_id'     => $childItem->getId(),
                        'type'        => $childItem->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                        'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $childItem->getName()),
                        'quantity'    => round($item->getQtyOrdered()),
                        'unitPrice'   => $this->mollieHelper->getAmountArray($currency, 0),
                        'totalAmount' => $this->mollieHelper->getAmountArray($currency, 0),
                        'vatRate'     => sprintf("%.2f", $childItem->getTaxPercent()),
                        'vatAmount'   => $this->mollieHelper->getAmountArray($currency, 0),
                        'sku'         => $childItem->getProduct()->getSku(),
                        'productUrl'  => $childItem->getProduct()->getProductUrl()
                    ];
                }
            }
        }

        if (!$order->getIsVirtual()) {

            /**
             * The total amount of the line, including VAT and discounts
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $this->getTotalAmountShipping($order, $forceBaseCurrency);

            $vatRate = $this->getShippingVatRate($order);

            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we recalculare this from totalAmount
             */
            $vatAmount = round($totalAmount * ($vatRate / (100 + $vatRate)), 2);

            $orderLine = [
                'item_id'     => '',
                'type'        => 'shipping_fee',
                'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $order->getShippingDescription()),
                'quantity'    => 1,
                'unitPrice'   => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'vatRate'     => sprintf("%.2f", $vatRate),
                'vatAmount'   => $this->mollieHelper->getAmountArray($currency, $vatAmount),
                'sku'         => $order->getShippingMethod()
            ];

            $orderLines[] = $orderLine;
        }

        if ($this->storeCredit->orderHasStoreCredit($order)) {
            $orderLines[] = $this->storeCredit->getOrderLine($order, $forceBaseCurrency);
        }

        if ($this->paymentFee->orderHasPaymentFee($order)) {
            $orderLines[] = $this->paymentFee->getOrderLine($order, $forceBaseCurrency);
        }

        if (!empty((float)$order->getBaseDiscountAmount()) || !empty((float)$order->getDiscountAmount())) {
            $orderLines[] = $this->getOrderDiscount($order, $forceBaseCurrency);
        }

        $this->saveOrderLines($orderLines, $order);
        foreach ($orderLines as &$orderLine) {
            unset($orderLine['item_id']);
        }

        return $orderLines;
    }

    /**
     * @param Item $item
     * @param      $forceBaseCurrency
     *
     * @return float
     */
    private function getTotalAmountOrderItem(Item $item, $forceBaseCurrency)
    {
        if ($item->getProductType() == ProductType::TYPE_BUNDLE) {
            return $forceBaseCurrency ? $item->getBaseRowTotalInclTax() : $item->getRowTotalInclTax();
        }

        if ($forceBaseCurrency) {
            return $item->getBaseRowTotal()
                - $item->getBaseDiscountAmount()
                + $item->getBaseTaxAmount()
                + $item->getBaseDiscountTaxCompensationAmount();
        }

        return $item->getRowTotal()
            - $item->getDiscountAmount()
            + $item->getTaxAmount()
            + $item->getDiscountTaxCompensationAmount();
    }

    /**
     * @param Item $item
     * @param      $forceBaseCurrency
     *
     * @return float
     */
    private function getDiscountAmountOrderItem(Item $item, $forceBaseCurrency)
    {
        if ($forceBaseCurrency) {
            return $item->getBaseDiscountAmount() + $item->getBaseDiscountTaxCompensationAmount();
        }

        return $item->getDiscountAmount() + $item->getDiscountTaxCompensationAmount();
    }

    /**
     * @param Order $order
     * @param       $forceBaseCurrency
     *
     * @return float
     */
    private function getTotalAmountShipping(Order $order, $forceBaseCurrency)
    {
        if ($forceBaseCurrency) {
            return $order->getBaseShippingAmount()
                + $order->getBaseShippingTaxAmount()
                + $order->getBaseShippingDiscountTaxCompensationAmnt();
        }

        return $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $order->getShippingDiscountTaxCompensationAmount();
    }

    /**
     * @param Order $order
     *
     * @return mixed
     */
    public function getShippingVatRate(Order $order)
    {
        $taxPercentage = 0;
        if ($order->getShippingAmount() > 0) {
            $taxPercentage = ($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100;
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
     * @param CreditmemoInterface $creditmemo
     * @param bool $addShipping
     * @return array
     */
    public function getCreditmemoOrderLines(CreditmemoInterface $creditmemo, $addShipping)
    {
        $orderLines = [];

        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            if (!$lineId) {
                continue;
            }

            $line = [
                'id' => $lineId,
                'quantity' => round($item->getQty()),
            ];

            if ($item->getBaseDiscountAmount()) {
                $line['amount'] = $this->mollieHelper->getAmountArray(
                    $creditmemo->getBaseCurrencyCode(),
                    $item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()
                );
            }

            $orderLines[] = $line;
        }

        $orderId = $creditmemo->getOrderId();
        if ($addShipping) {
            $shippingFeeItemLine = $this->getShippingFeeItemLineOrder($orderId);
            $orderLines[] = ['id' => $shippingFeeItemLine->getLineId(), 'quantity' => 1];
        }

        $storeCreditLine = $this->getStoreCreditItemLineOrder($orderId);
        if ($storeCreditLine->getId()) {
            $orderLines[] = ['id' => $storeCreditLine->getLineId(), 'quantity' => 1];
        }

        $paymentFeeCreditLine = $this->getPaymentFeeCreditItemLineOrder($orderId);
        if ($paymentFeeCreditLine->getId() && !$this->creditmemoService->hasItemsLeftToRefund($creditmemo)) {
            $orderLines[] = ['id' => $paymentFeeCreditLine->getLineId(), 'quantity' => 1];
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
     * @return OrderLines
     */
    public function getStoreCreditItemLineOrder($orderId)
    {
        $storeCreditLine = $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->addFieldToFilter('type', ['eq' => 'store_credit'])
            ->getLastItem();

        return $storeCreditLine;
    }

    /**
     * @param $orderId
     *
     * @return OrderLines
     */
    public function getPaymentFeeCreditItemLineOrder($orderId)
    {
        return $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->addFieldToFilter('type', ['eq' => 'surcharge'])
            ->getLastItem();
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

    /**
     * @param OrderInterface $order
     * @param int $forceBaseCurrency
     * @return array
     */
    private function getOrderDiscount(OrderInterface $order, $forceBaseCurrency)
    {
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = $forceBaseCurrency ? $order->getBaseDiscountAmount() : $order->getDiscountAmount();

        return [
            'name' => 'Discount',
            'type' => 'discount',
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, $amount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, $amount),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, 0),
            'quantity' => 1,
        ];
    }
}
