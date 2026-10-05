<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;

class OrderAmount
{
    public function __construct(
        private Config $config,
        private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private OrderRepositoryInterface $orderRepository,
        private General $mollieHelper
    ) {}

    /**
     * @throws LocalizedException
     */
    public function getByTransactionId(string $transactionId): array
    {
        $orders = $this->getOrders($transactionId);
        if ($orders === []) {
            throw new LocalizedException(__('No orders found for transaction %1', $transactionId));
        }

        return $this->getForOrders($orders);
    }

    /**
     * An order can have multiple Mollie transactions (for example when the customer is redirected twice), while
     * only one of them is stored on the order. The transaction currently being processed may therefore not be
     * found on any order. In that case the amount of the order itself is used.
     *
     * @throws LocalizedException
     */
    public function forOrder(OrderInterface $order): array
    {
        $orders = $this->getOrders((string) $order->getMollieTransactionId());
        if ($orders === []) {
            $orders = [$order];
        }

        return $this->getForOrders($orders);
    }

    /**
     * @param OrderInterface[] $orders
     * @throws LocalizedException
     */
    private function getForOrders(array $orders): array
    {
        $amount = 0.00;
        $currencies = [];
        foreach ($orders as $order) {
            if ($this->config->useBaseCurrency(storeId($order->getStoreId()))) {
                $currencies[] = $order->getBaseCurrencyCode();
                $amount += $order->getBaseGrandTotal();
            } else {
                $currencies[] = $order->getOrderCurrencyCode();
                $amount += $order->getGrandTotal();
            }
        }

        $this->validateCurrencies($currencies);

        return $this->mollieHelper->getAmountArray(reset($currencies), $amount);
    }

    /**
     * @return OrderInterface[]
     */
    private function getOrders(string $transactionId): array
    {
        if ($transactionId === '') {
            return [];
        }

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('mollie_transaction_id', $transactionId);

        return $this->orderRepository->getList($searchCriteriaBuilder->create())->getItems();
    }

    /**
     * @param array $currencies
     * @throws LocalizedException
     */
    protected function validateCurrencies(array $currencies)
    {
        if (count(array_unique($currencies)) > 1) {
            throw new LocalizedException(__(
                'The orders have different currencies (%1)',
                implode(', ', array_unique($currencies)),
            ));
        }
    }
}
