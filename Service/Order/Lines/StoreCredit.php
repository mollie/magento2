<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Exceptions\NoStoreCreditFound;
use Mollie\Payment\Helper\General;

class StoreCredit
{
    /**
     * StoreCredit constructor.
     * @param General $mollieHelper
     * @param OrderLinesProcessor $orderLinesProcessor
     */
    public function __construct(
        private General $mollieHelper,
        private OrderLinesProcessor $orderLinesProcessor
    ) {}

    public function orderHasStoreCredit(OrderInterface $order): bool
    {
        if ($order->getData('customer_balance_amount')) {
            return true;
        }

        if ($order->getData('amstorecredit_amount')) {
            return true;
        }

        return false;
    }

    public function creditmemoHasStoreCredit(CreditmemoInterface $creditmemo): bool
    {
        if ($creditmemo->getData('customer_balance_amount')) {
            return true;
        }

        if ($creditmemo->getData('amstorecredit_amount')) {
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
    public function getOrderLine(OrderInterface $order, $forceBaseCurrency): array
    {
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $unitPrice = $this->getUnitPrice($order, $forceBaseCurrency);
        $vatRate = $this->getVatRate();
        $vatAmount = $this->getVatAmount();

        $orderLine = [
            'type' => 'store_credit',
            'name' => __('Store Credit'),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, -$unitPrice),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, -$unitPrice),
            'vatRate' => $vatRate,
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, $vatAmount),
        ];

        return $this->orderLinesProcessor->process($orderLine, $order);
    }

    /**
     * @param OrderInterface $order
     * @param bool $forceBaseCurrency
     * @return float
     * @throws NoStoreCreditFound
     */
    private function getUnitPrice(OrderInterface $order, $forceBaseCurrency)
    {
        if ($order->getData('customer_balance_amount')) {
            return $forceBaseCurrency ?
                $order->getData('base_customer_balance_amount') :
                $order->getData('customer_balance_amount');
        }

        if ($order->getData('amstorecredit_amount')) {
            return $forceBaseCurrency ?
                $order->getData('amstorecredit_base_amount') :
                $order->getData('amstorecredit_amount');
        }

        throw new NoStoreCreditFound(
            __(
                'We were unable to find the store credit for order #%1',
                $order->getEntityId(),
            ),
        );
    }

    /**
     * The current implementations, Magento Enterprise Store Credit and  Amasty Store Credit,
     * don't support tax on the amounts.
     */
    private function getVatRate(): float
    {
        return 0.0;
    }

    /**
     * The current implementations, Magento Enterprise Store Credit and  Amasty Store Credit,
     * don't support tax on the amounts.
     */
    private function getVatAmount(): float
    {
        return 0.0;
    }
}
