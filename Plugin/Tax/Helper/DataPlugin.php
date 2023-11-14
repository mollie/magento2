<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Tax\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Tax\Item;
use Magento\Tax\Api\Data\OrderTaxDetailsItemInterface;
use Magento\Tax\Api\OrderTaxManagementInterface;

class DataPlugin
{
    /**
     * @var OrderTaxManagementInterface
     */
    private $orderTaxManagement;

    public function __construct(
        OrderTaxManagementInterface $orderTaxManagement
    ) {
        $this->orderTaxManagement = $orderTaxManagement;
    }

    public function afterGetCalculatedTaxes(object $callable, array $result, $source): array
    {
        if (!$source instanceof InvoiceInterface &&
            !$source instanceof CreditmemoInterface
        ) {
            return $result;
        }

        $order = $source->getOrder();
        $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($order->getId());

        $items = array_filter($orderTaxDetails->getItems(), function (Item $item) {
            return $item->getType() == 'mollie_payment_fee_tax';
        });

        if (count($items) === 0) {
            return $result;
        }

        foreach ($items as $item) {
            $result = $this->aggregateTaxes($result, $item, 1);
        }

        return $result;
    }

    /**
     * Copied from \Magento\Tax\Helper\Data::_aggregateTaxes
     *
     * @param $taxClassAmount
     * @param OrderTaxDetailsItemInterface $itemTaxDetail
     * @param $ratio
     * @return array
     */
    private function aggregateTaxes($taxClassAmount, OrderTaxDetailsItemInterface $itemTaxDetail, $ratio)
    {
        $itemAppliedTaxes = $itemTaxDetail->getAppliedTaxes();
        foreach ($itemAppliedTaxes as $itemAppliedTax) {
            $taxAmount = $itemAppliedTax->getAmount() * $ratio;
            $baseTaxAmount = $itemAppliedTax->getBaseAmount() * $ratio;

            if (0 == $taxAmount && 0 == $baseTaxAmount) {
                continue;
            }
            $taxCode = $this->getKeyByName($taxClassAmount, $itemAppliedTax->getCode());
            if (!isset($taxClassAmount[$taxCode])) {
                $taxClassAmount[$taxCode]['title'] = $itemAppliedTax->getTitle();
                $taxClassAmount[$taxCode]['percent'] = $itemAppliedTax->getPercent();
                $taxClassAmount[$taxCode]['tax_amount'] = $taxAmount;
                $taxClassAmount[$taxCode]['base_tax_amount'] = $baseTaxAmount;
            } else {
                $taxClassAmount[$taxCode]['tax_amount'] += $taxAmount;
                $taxClassAmount[$taxCode]['base_tax_amount'] += $baseTaxAmount;
            }
        }

        return $taxClassAmount;
    }

    /**
     * @param array $taxClassAmount
     * @param string $name
     * @return string|int
     */
    private function getKeyByName(array $taxClassAmount, string $name)
    {
        foreach ($taxClassAmount as $key => $tax) {
            if ($tax['title'] === $name) {
                return $key;
            }
        }

        return $name;
    }
}
