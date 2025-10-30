<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines;

use Laminas\Uri\Http;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Model\OrderLinesFactory;
use Mollie\Payment\Model\ResourceModel\OrderLines\CollectionFactory;

class Order
{
    private ?string $currency;

    private ?bool $forceBaseCurrency = null;

    private ?OrderInterface $order = null;

    public function __construct(
        private General $mollieHelper,
        private StoreCredit $storeCredit,
        private PaymentFee $paymentFee,
        private CollectionFactory $orderLinesCollection,
        private OrderLinesFactory $orderLinesFactory,
        private OrderLinesProcessor $orderLinesProcessor,
        private OrderLinesGenerator $orderLinesGenerator,
        private StoreManagerInterface $storeManager,
        private Http $http,
    ) {
    }

    public function get(OrderInterface $order): array
    {
        $this->order = $order;
        $this->forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
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

        $orderLines = $this->orderLinesGenerator->execute($order, $orderLines);

        // The adjustment line should be the last one. This corrects any rounding issues.
        if ($adjustment = $this->getAdjustment($order, $orderLines)) {
            $orderLines[] = $adjustment;
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
    private function getOrderLine(OrderItemInterface $item, bool $zeroPriceLine = false): array
    {
        /**
         * The total amount of the line, including VAT and discounts
         * Should Match: (unitPrice × quantity) - discountAmount
         * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
         */
        $totalAmount = $this->getTotalAmountOrderItem($item) ?? 0.0;

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
            'type' => $item->getIsVirtual() !== null && (int) $item->getIsVirtual() !== 1 ? 'physical' : 'digital',
            'description' => preg_replace('/[^\p{L}\p{N} -]/u', '', $item->getName() ?? ''),
            'quantity' => round((float)$item->getQtyOrdered()),
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, $unitPrice),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, $totalAmount),
            'vatRate' => sprintf('%.2f', $item->getTaxPercent()),
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, $vatAmount),
            'sku' => substr($item->getSku() ?? '', 0, 64),
            'productUrl' => $this->getProductUrl($item),
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
    protected function getShippingOrderLine(OrderInterface $order): array
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

        $name = preg_replace('/[^A-Za-z0-9 -]/', '', $order->getShippingDescription() ?? '');
        if (!$name) {
            $name = $order->getShippingMethod();
        }

        $orderLine = [
            'item_id' => '',
            'type' => 'shipping_fee',
            'description' => $name,
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, $totalAmount),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, $totalAmount),
            'vatRate' => sprintf('%.2f', $vatRate),
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, $vatAmount),
            'sku' => $order->getShippingMethod(),
        ];

        return $this->orderLinesProcessor->process($orderLine, $this->order);
    }

    /**
     * @param OrderItemInterface $item
     *
     * @return float
     */
    private function getTotalAmountOrderItem(OrderItemInterface $item): ?float
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
    private function getDiscountAmountOrderItem(OrderItemInterface $item): float|int
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
    private function getTotalAmountShipping(OrderInterface $order): float|int|array
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

    public function getShippingVatRate(OrderInterface $order): int|float
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
    public function saveOrderLines($orderLines, OrderInterface $order): void
    {
        $existingItems = $this->orderLinesCollection->create()
            ->addFieldToFilter('order_id', ['eq' => $order->getEntityId()]);

        // When the orderLines already exists, do not create again.
        if ($existingItems->getSize() == count($orderLines)) {
            return;
        }

        foreach ($orderLines as $line) {
            /** @var OrderLines $orderLine */
            $orderLine = $this->orderLinesFactory->create();
            // @phpstan-ignore-next-line TODO: Make a proper repository for this
            $orderLine->addData($line)->setOrderId($order->getId())->save();
        }
    }

    /**
     * @param OrderItemInterface $item
     * @return string|null
     */
    private function getProductUrl(OrderItemInterface $item): ?string
    {
        $product = $item->getProduct();
        if (!$product) {
            return null;
        }

        // Magento allows some weird characters the product url, but Mollie does not. So if the URL contains invalid
        // characters we will return the base url with the direct url to the controller.
        // Magento bug: https://github.com/magento/magento2/issues/26672
        $url = $product->getProductUrl();
        $path = $this->http->parse($url)->getPath();
        // Allow a-z, A-Z, "-", "/" and ".". If anything else is present return the catalog/product/view url.
        if (preg_match('#[^a-zA-Z-/.]#', $path)) {
            $baseUrl = rtrim($this->storeManager->getStore()->getBaseUrl(), '/');

            return $baseUrl . '/catalog/product/view/id/' . $item->getProductId();
        }

        return $url;
    }

    private function getAdjustment(OrderInterface $order, array $orderLines): ?array
    {
        $orderLinesTotal = 0;
        foreach ($orderLines as $orderLine) {
            $orderLinesTotal += $orderLine['totalAmount']['value'];
        }

        $grandTotal = $order->getGrandTotal();
        if ($this->forceBaseCurrency) {
            $grandTotal = $order->getBaseGrandTotal();
        }

        $max = $orderLinesTotal + 0.05;
        $min = $orderLinesTotal - 0.05;
        if ($grandTotal < $min || $grandTotal > $max) {
            return null;
        }

        $difference = round(round((float)$grandTotal, 2) - round((float)$orderLinesTotal, 2), 2);
        if (abs($difference) < 0.01) {
            return null;
        }

        return [
            'item_id' => '',
            'type' => 'discount',
            'description' => 'Adjustment',
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, $difference),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, $difference),
            'vatRate' => sprintf('%.2f', 0),
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, 0),
            'sku' => 'adjustment',
        ];
    }
}
