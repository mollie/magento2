<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;

class OrderAmount
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var General
     */
    private $mollieHelper;

    public function __construct(
        Config $config,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OrderRepositoryInterface $orderRepository,
        General $mollieHelper
    ) {
        $this->config = $config;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->orderRepository = $orderRepository;
        $this->mollieHelper = $mollieHelper;
    }

    /**
     * @param string $transactionId
     * @throws LocalizedException
     * @return array
     */
    public function getByTransactionId(string $transactionId): array
    {
        $amount = 0.00;
        $currencies = [];
        $orders = $this->getOrders($transactionId);
        foreach ($orders->getItems() as $order) {
            if ($this->config->useBaseCurrency($order->getStoreId())) {
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

    private function getOrders(string $transactionId): OrderSearchResultInterface
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('mollie_transaction_id', $transactionId);

        return $this->orderRepository->getList($searchCriteriaBuilder->create());
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
                implode(', ', array_unique($currencies))
            ));
        }
    }
}
