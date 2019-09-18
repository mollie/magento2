<?php


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
     * StoreCredit constructor.
     * @param General $mollieHelper
     */
    public function __construct(
        General $mollieHelper
    ) {
        $this->mollieHelper = $mollieHelper;
    }

    public function orderHasPaymentFee(OrderInterface $order)
    {
        return $order->getData('base_mollie_payment_fee') && $order->getData('mollie_payment_fee');
    }

    public function getOrderLine(OrderInterface $order, $forceBaseCurrency)
    {
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $amount = $order->getData('base_mollie_payment_fee');
        $taxAmount = $order->getData('base_mollie_payment_fee_tax');

        $vatRate = 0;
        if ($taxAmount) {
            $vatRate = round(($taxAmount / $amount) * 100, 2);
        }

        return [
            'type' => 'surcharge',
            'name' => __('Payment Fee'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, $amount + $taxAmount),
            'vatRate' => $vatRate,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, $taxAmount),
        ];
    }
}
