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

class GeisswebEuvat implements GeneratorInterface
{
    public function __construct(
        private General $mollieHelper,
        private Manager $moduleManager
    ) {}

    /**
     * The Geissweb_Euvat module messes with the trigger_recollect in
     * vendor/geissweb/module-euvat/Plugin/Model/Quote.php
     *
     * which leads to invalid order lines being generated. This is a workaround for that.
     *
     * @param OrderInterface $order
     * @param array $orderLines
     * @return array
     */
    public function process(OrderInterface $order, array $orderLines): array
    {
        if (!$this->moduleManager->isEnabled('Geissweb_Euvat')) {
            return $orderLines;
        }

        $forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        $orderTotal = $forceBaseCurrency ? $order->getBaseGrandTotal() : $order->getGrandTotal();
        $orderLinesTotal = $this->getOrderLinesTotal($orderLines);

        if ($orderTotal == $orderLinesTotal) {
            return $orderLines;
        }

        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $orderLines[] = [
            'type' => 'discount',
            'description' => 'EU VAT',
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, $orderTotal - $orderLinesTotal),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, $orderTotal - $orderLinesTotal),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, 0),
        ];

        return $orderLines;
    }

    private function getOrderLinesTotal(array $orderLines): float
    {
        $total = 0;
        foreach ($orderLines as $orderLine) {
            $total += $orderLine['totalAmount']['value'];
        }

        return $total;
    }
}
