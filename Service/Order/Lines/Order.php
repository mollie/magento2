<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Model\OrderLinesFactory;

class Order
{
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var StoreCredit
     */
    private $storeCredit;

    /**
     * @var PaymentFee
     */
    private $paymentFee;

    /**
     * @var OrderLinesFactory
     */
    private $orderLinesFactory;

    public function __construct(
        MollieHelper $mollieHelper,
        StoreCredit $storeCredit,
        PaymentFee $paymentFee,
        OrderLinesFactory $orderLinesFactory
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->storeCredit = $storeCredit;
        $this->paymentFee = $paymentFee;
        $this->orderLinesFactory = $orderLinesFactory;
    }

    public function get(OrderInterface $order)
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
                        'quantity'    => round($childItem->getQtyOrdered()),
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
     * @param OrderInterface $order
     * @param                $forceBaseCurrency
     *
     * @return float
     */
    private function getTotalAmountShipping(OrderInterface $order, $forceBaseCurrency)
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
     * @param OrderInterface $order
     *
     * @return mixed
     */
    public function getShippingVatRate(OrderInterface $order)
    {
        $taxPercentage = 0;
        if ($order->getShippingAmount() > 0) {
            $taxPercentage = ($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100;
        }

        return $taxPercentage;
    }

    /**
     * @param OrderInterface $order
     * @param                $orderLines
     */
    public function saveOrderLines($orderLines, OrderInterface $order)
    {
        foreach ($orderLines as $line) {
            /** @var OrderLines $orderLine */
            $orderLine = $this->orderLinesFactory->create();
            $orderLine->addData($line)->setOrderId($order->getId())->save();
        }
    }
}
