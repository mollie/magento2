<?php

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Exceptions\NoStoreCreditFound;
use Mollie\Payment\Helper\General;

class StoreCredit
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

    public function orderHasStoreCredit(OrderInterface $order)
    {
        if ($order->getData('amstorecredit_amount')) {
            return true;
        }

        return false;
    }

    /**
     * @param OrderInterface $order
     * @param bool $forceBaseCurrency
     * @return array
     * @throws NoStoreCreditFound
     */
    public function getOrderLine(OrderInterface $order, $forceBaseCurrency)
    {
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $unitPrice = $this->getUnitPrice($order, $forceBaseCurrency);
        $vatRate = $this->getVatRate($order, $forceBaseCurrency);
        $vatAmount = $this->getVatAmount($order, $forceBaseCurrency);

        return [
            'type' => 'store_credit',
            'name' => __('Store Credit'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$unitPrice),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$unitPrice),
            'vatRate' => $vatRate,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, $vatAmount),
        ];
    }

    /**
     * @param OrderInterface $order
     * @param bool $forceBaseCurrency
     * @return float
     * @throws NoStoreCreditFound
     */
    private function getUnitPrice(OrderInterface $order, $forceBaseCurrency)
    {
        if ($order->getData('amstorecredit_amount')) {
            return $forceBaseCurrency ?
                $order->getData('amstorecredit_base_amount') :
                $order->getData('amstorecredit_amount');
        }

        throw new NoStoreCreditFound(
            __(
                'We where unable to find the store credit for order #%1',
                $order->getEntityId()
            )
        );
    }

    /**
     * There's only an implementation for the Amasty Store Credit, which doesn't support tax on the amounts.
     *
     * @param OrderInterface $order
     * @param bool $forceBaseCurrency
     * @return string
     */
    private function getVatRate(OrderInterface $order, $forceBaseCurrency)
    {
        return '0.00';
    }

    /**
     * There's only an implementation for the Amasty Store Credit, which doesn't support tax on the amounts.
     *
     * @param OrderInterface $order
     * @param bool $forceBaseCurrency
     * @return string
     */
    private function getVatAmount(OrderInterface $order, $forceBaseCurrency)
    {
        return '0.00';
    }
}