<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\ResourceModel\OrderLines\Collection as OrderLinesCollection;
use Mollie\Payment\Model\ResourceModel\OrderLines\CollectionFactory as OrderLinesCollectionFactory;
use Mollie\Payment\Service\Order\Creditmemo as CreditmemoService;
use Mollie\Payment\Service\Order\Lines\Order as OrderOrderLines;

/**
 * @method int getId()
 * @method int getItemId()
 * @method string getLineId()
 * @method int getOrderId()
 * @method string getType()
 * @method string getSku()
 * @method int getQtyOrdered()
 * @method int getQtyPaid()
 * @method int getQtyCanceled()
 * @method int getQtyShipped()
 * @method int getQtyRefunded()
 * @method float getUnitPrice()
 * @method float getDiscountAmount()
 * @method float getTotalAmount()
 */
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
     * @var CreditmemoService
     */
    private $creditmemoService;
    /**
     * @var State
     */
    private $orderState;
    /**
     * @var OrderOrderLines
     */
    private $orderOrderLines;

    /**
     * OrderLines constructor.
     *
     * @param MollieHelper                $mollieHelper
     * @param OrderLinesFactory           $orderLinesFactory
     * @param OrderLinesCollectionFactory $orderLinesCollection
     * @param Context                     $context
     * @param Registry                    $registry
     * @param CreditmemoService           $creditmemoService
     * @param OrderOrderLines             $orderOrderLines
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
        CreditmemoService $creditmemoService,
        OrderOrderLines $orderOrderLines,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->orderLinesFactory = $orderLinesFactory;
        $this->orderLinesCollection = $orderLinesCollection;
        $this->creditmemoService = $creditmemoService;
        $this->orderOrderLines = $orderOrderLines;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get Order lines of Order
     *
     * @param OrderInterface $order
     *
     * @return array
     * @deprecated since v1.9.0
     * @see \Mollie\Payment\Service\Order\Lines\Order
     */
    public function getOrderLines(OrderInterface $order)
    {
        return $this->orderOrderLines->get($order);
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
     * @param array $orderLines
     * @param bool $paid
     */
    public function updateOrderLinesByWebhook(array $orderLines, bool $paid = false)
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
     * @param ShipmentInterface $shipment
     * @return array
     */
    public function getShipmentOrderLines(ShipmentInterface $shipment): array
    {
        $orderLines = [];

        /** @var OrderInterface $order */
        $order = $shipment->getOrder();
        $orderHasDiscount = abs($order->getDiscountAmount() ?? 0) > 0;

        /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
        foreach ($shipment->getItemsCollection() as $item) {
            if (!$item->getQty()) {
                continue;
            }

            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            $line = ['id' => $lineId, 'quantity' => $item->getQty()];

            if ($orderHasDiscount) {
                $orderItem = $item->getOrderItem();
                $rowTotal = $orderItem->getBaseRowTotal()
                    - $orderItem->getBaseDiscountAmount()
                    + $orderItem->getBaseTaxAmount()
                    + $orderItem->getBaseDiscountTaxCompensationAmount();

                $line['amount'] = $this->mollieHelper->getAmountArray(
                    $order->getBaseCurrencyCode(),
                    (($rowTotal) / $orderItem->getQtyOrdered()) * $item->getQty()
                );
            }

            $orderLines[] = $line;
        }

        if ($order->getShipmentsCollection()->count() === 0) {
            $this->addNonProductItems($order, $orderLines);
        }

        return ['lines' => $orderLines];
    }

    private function addNonProductItems(OrderInterface $order, array &$orderLines): void
    {
        $collection = $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $order->getEntityId()])
            ->addFieldToFilter('type', ['nin' => ['physical', 'digital']]);

        /** @var OrderLines $item */
        foreach ($collection as $item) {
            $orderLines[] = [
                'id' => $item->getLineId(),
                'quantity' => 1,
            ];
        }
    }

    /**
     * @param $itemId
     *
     * @return OrderLines
     */
    public function getOrderLineByItemId($itemId)
    {
        return $this->orderLinesCollection->create()
            ->addFieldToFilter('item_id', ['eq' => $itemId])
            ->addFieldToFilter('line_id', ['notnull' => true])
            ->getLastItem();
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @param bool $addShipping
     * @return array
     */
    public function getCreditmemoOrderLines(CreditmemoInterface $creditmemo, bool $addShipping): array
    {
        $orderLines = [];

        /** @var CreditmemoItemInterface $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            if (!$lineId || $this->shouldSkipCreditmemoForBundleProduct($item)) {
                continue;
            }

            $line = [
                'id' => $lineId,
                'quantity' => round($item->getQty()),
            ];

            if ($item->getBaseDiscountAmount()) {
                $rowTotal = $item->getBaseRowTotal()
                    - $item->getBaseDiscountAmount()
                    + $item->getBaseTaxAmount()
                    + $item->getBaseDiscountTaxCompensationAmount();

                $line['amount'] = $this->mollieHelper->getAmountArray($creditmemo->getBaseCurrencyCode(), $rowTotal);
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

    private function shouldSkipCreditmemoForBundleProduct(CreditmemoItemInterface $item): bool
    {
        if ($item->getOrderItem()->getProductType() != 'bundle') {
            return false;
        }

        if ($item->getOrderItem()->getProductOptionByCode('product_calculations') == 1) {
            return false;
        }

        return true;
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
