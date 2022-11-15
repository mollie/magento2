<?php

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General;

class GeisswebEuvat implements GeneratorInterface
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

        $forceBaseCurrency = (bool)$this->mollieHelper->useBaseCurrency($order->getStoreId());
        $orderTotal = $forceBaseCurrency ? $order->getBaseGrandTotal() : $order->getGrandTotal();
        $orderLinesTotal = $this->getOrderLinesTotal($orderLines);

        if ($orderTotal == $orderLinesTotal) {
            return $orderLines;
        }

        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        $orderLines[] = [
            'type' => 'discount',
            'name' => 'EU VAT',
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, $orderTotal - $orderLinesTotal),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, $orderTotal - $orderLinesTotal),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, 0)
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
