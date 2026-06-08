<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General;

class FoomanTotals implements GeneratorInterface
{
    public function __construct(
        private General $mollieHelper,
        private Manager $moduleManager
    ) {}

    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$this->moduleManager->isEnabled('Fooman_Totals')) {
            return $orderLines;
        }

        $extAttr = $order->getExtensionAttributes();
        if (!$extAttr) {
            return $orderLines;
        }

        $foomanGroup = $extAttr->getFoomanTotalGroup();
        if (empty($foomanGroup)) {
            return $orderLines;
        }

        $totals = $foomanGroup->getItems();
        if (empty($totals)) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        foreach ($totals as $total) {
            $amount = $forceBaseCurrency ? $total->getBaseAmount() : $total->getAmount();
            $taxAmount = $forceBaseCurrency ? $total->getBaseTaxAmount() : $total->getTaxAmount();

            $vatRate = 0;
            if ($taxAmount && $amount != 0) {
                $vatRate = round(($taxAmount / $amount) * 100, 2);
            }

            if (abs($amount + $taxAmount) < 0.01) {
                return $orderLines;
            }

            $orderLines[] = [
                'type' => 'surcharge',
                'description' => $total->getLabel(),
                'quantity' => 1,
                'unitPrice' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
                'vatRate' => $vatRate,
                'vatAmount' => $this->mollieHelper->getAmountArray($currency, $taxAmount),
            ];
        }

        return $orderLines;
    }
}
