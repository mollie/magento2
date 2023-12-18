<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Module\Manager;
use Mollie\Payment\Helper\General;

class FoomanTotals implements GeneratorInterface
{
    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        General $mollieHelper,
        Manager $moduleManager
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->moduleManager = $moduleManager;
    }

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

        $forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
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
                'name' =>  $total->getLabel(),
                'quantity' => 1,
                'unitPrice' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
                'vatRate' => $vatRate,
                'vatAmount' => $this->mollieHelper->getAmountArray($currency, $taxAmount)
            ];
        }

        return $orderLines;
    }
}
