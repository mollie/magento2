<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
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

    /**
     * @var string|null
     */
    private $currency;

    /**
     * @var bool
     */
    private $forceBaseCurrency;

    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @var OrderLinesProcessor
     */
    private $orderLinesProcessor;

    public function __construct(
        MollieHelper $mollieHelper,
        StoreCredit $storeCredit,
        PaymentFee $paymentFee,
        OrderLinesFactory $orderLinesFactory,
        OrderLinesProcessor $orderLinesProcessor
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->storeCredit = $storeCredit;
        $this->paymentFee = $paymentFee;
        $this->orderLinesFactory = $orderLinesFactory;
        $this->orderLinesProcessor = $orderLinesProcessor;
    }

    public function get(OrderInterface $order)
    {
        $this->order = $order;
        $this->forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
        $this->currency = $this->forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $orderLines = [];

        /** @var OrderItemInterface $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $isBundleProduct = $item->getProductType() == ProductType::TYPE_BUNDLE;
            $isZeroPriceLine = $isBundleProduct && $item->getProduct() && $item->getProduct()->getPriceType() == 0;
            $orderLines[] = $this->getOrderLine($item, $isZeroPriceLine);

            if ($isBundleProduct) {
                /** @var OrderItemInterface $childItem */
                foreach ($item->getChildrenItems() as $childItem) {
                    $orderLines[] = $this->getOrderLine($childItem);
                }
            }
        }

        if (!$order->getIsVirtual()) {
            $orderLines[] = $this->getShippingOrderLine($order);
        }

        if ($this->storeCredit->orderHasStoreCredit($order)) {
            $orderLines[] = $this->storeCredit->getOrderLine($order, $this->forceBaseCurrency);
        }

        if ($this->paymentFee->orderHasPaymentFee($order)) {
            $orderLines[] = $this->paymentFee->getOrderLine($order, $this->forceBaseCurrency);
        }

        $this->saveOrderLines($orderLines, $order);
        foreach ($orderLines as &$orderLine) {
            unset($orderLine['item_id']);
        }

        return $orderLines;
    }

    /**
     * @param OrderItemInterface $item
     * @param bool $zeroPriceLine Sometimes the line must be present but the amount must be zero. (mostly for bundles)
     * @return array
     */
    private function getOrderLine(OrderItemInterface $item, $zeroPriceLine = false)
    {
        /**
         * The total amount of the line, including VAT and discounts
         * Should Match: (unitPrice × quantity) - discountAmount
         * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
         */
        $totalAmount = $this->getTotalAmountOrderItem($item);

        /**
         * The total discount amount of the line.
         */
        $discountAmount = $this->getDiscountAmountOrderItem($item);

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

        if ($zeroPriceLine) {
            $totalAmount = 0;
            $discountAmount = 0;
            $unitPrice = 0;
            $vatAmount = 0;
        }

        $orderLine = [
            'item_id' => $item->getId(),
            'type' => $item->getProductType() != 'downloadable' ? 'physical' : 'digital',
            'name' => preg_replace("/[^A-Za-z0-9 -]/", "", $item->getName()),
            'quantity' => round($item->getQtyOrdered()),
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, $unitPrice),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, $totalAmount),
            'vatRate' => sprintf("%.2f", $item->getTaxPercent()),
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, $vatAmount),
            'sku' => substr($item->getSku(), 0, 64),
            'productUrl' => $item->getProduct() ? $item->getProduct()->getProductUrl() : null,
        ];

        if ($discountAmount) {
            $orderLine['discountAmount'] = $this->mollieHelper->getAmountArray($this->currency, $discountAmount);
        }

        return $this->orderLinesProcessor->process($orderLine, $this->order, $item);
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    protected function getShippingOrderLine(OrderInterface $order)
    {
        /**
         * The total amount of the line, including VAT and discounts
         * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
         */
        $totalAmount = $this->getTotalAmountShipping($order);

        $vatRate = $this->getShippingVatRate($order);

        /**
         * The amount of VAT on the line.
         * Should Match: totalAmount × (vatRate / (100 + vatRate)).
         * Due to Mollie API requirements, we recalculare this from totalAmount
         */
        $vatAmount = round($totalAmount * ($vatRate / (100 + $vatRate)), 2);

        $orderLine = [
            'item_id' => '',
            'type' => 'shipping_fee',
            'name' => preg_replace("/[^A-Za-z0-9 -]/", "", $order->getShippingDescription()),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, $totalAmount),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, $totalAmount),
            'vatRate' => sprintf("%.2f", $vatRate),
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, $vatAmount),
            'sku' => $order->getShippingMethod()
        ];

        return $this->orderLinesProcessor->process($orderLine, $this->order);
    }

    /**
     * @param OrderItemInterface $item
     *
     * @return float
     */
    private function getTotalAmountOrderItem(OrderItemInterface $item)
    {
        if ($item->getProductType() == ProductType::TYPE_BUNDLE) {
            return $this->forceBaseCurrency ? $item->getBaseRowTotalInclTax() : $item->getRowTotalInclTax();
        }

        if ($this->forceBaseCurrency) {
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
     * @param OrderItemInterface $item
     *
     * @return float
     */
    private function getDiscountAmountOrderItem(OrderItemInterface $item)
    {
        if ($this->forceBaseCurrency) {
            return abs($item->getBaseDiscountAmount() + $item->getBaseDiscountTaxCompensationAmount());
        }

        return abs($item->getDiscountAmount() + $item->getDiscountTaxCompensationAmount());
    }

    /**
     * @param OrderInterface $order
     *
     * @return float
     */
    private function getTotalAmountShipping(OrderInterface $order)
    {
        if ($this->forceBaseCurrency) {
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
