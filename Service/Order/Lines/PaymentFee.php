<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General;

class PaymentFee
{
    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var OrderLinesProcessor
     */
    private $orderLinesProcessor;

    /**
     * StoreCredit constructor.
     * @param General $mollieHelper
     * @param OrderLinesProcessor $orderLinesProcessor
     */
    public function __construct(
        General $mollieHelper,
        OrderLinesProcessor $orderLinesProcessor
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->orderLinesProcessor = $orderLinesProcessor;
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function orderHasPaymentFee(OrderInterface $order)
    {
        return (float)$order->getData('base_mollie_payment_fee') && (float)$order->getData('mollie_payment_fee');
    }

    /**
     * @param OrderInterface $order
     * @param $forceBaseCurrency
     * @return array
     */
    public function getOrderLine(OrderInterface $order, $forceBaseCurrency): array
    {
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = $order->getData('base_mollie_payment_fee');
        $taxAmount = $order->getData('base_mollie_payment_fee_tax');

        $vatRate = 0;
        if ($taxAmount) {
            $vatRate = round(($taxAmount / $amount) * 100, 2);
        }

        $orderLine = [
            'type' => 'surcharge',
            'name' => __('Payment Fee'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
            'vatRate' => $vatRate,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, $taxAmount),
        ];

        return $this->orderLinesProcessor->process($orderLine, $order);
    }
}
