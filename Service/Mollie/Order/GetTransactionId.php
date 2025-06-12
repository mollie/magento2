<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderManagementInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class GetTransactionId
{
    /**
     * @var TransactionToOrderManagementInterface
     */
    private $transactionToOrderManagement;
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;
    /**
     * @var General
     */
    private $mollieHelper;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        TransactionToOrderManagementInterface $transactionToOrderManagement,
        MollieApiClient $mollieApiClient,
        General $mollieHelper,
        Config $config
    ) {
        $this->transactionToOrderManagement = $transactionToOrderManagement;
        $this->mollieApiClient = $mollieApiClient;
        $this->mollieHelper = $mollieHelper;
        $this->config = $config;
    }

    /**
     * Sometimes an order gets multiple transactions. If that's the case, this code will return the first
     * transaction that has the status 'paid'. This is to prevent issues where one transaction is 'paid',
     * but the other 'pending' transaction transitions to 'canceled'. This code prevents the order to get
     * canceled in that case.
     *
     *
     * @param OrderInterface $order
     * @return string|null
     */
    public function forOrder(OrderInterface $order): ?string
    {
        $transactions = array_map(function (TransactionToOrderInterface $transactionToOrder) {
            return $transactionToOrder->getTransactionId();
        }, $this->transactionToOrderManagement->getForOrder((int)$order->getEntityId()));

        if (!$transactions) {
            return null;
        }

        if (count($transactions) === 1) {
            return $order->getMollieTransactionId();
        }

        $this->config->addToLog('warning', [
            'Multiple transactions found for order #' . $order->getIncrementId() . '/' . $order->getEntityId(),
            $transactions
        ]);

        $statuses = $this->getTransactionStatuses($order, $transactions);
        foreach ($statuses as $transactionId => $status) {
            if ($status === 'paid') {
                $order->setMollieTransactionId($transactionId);
                return $transactionId;
            }
        }

        return $order->getMollieTransactionId();
    }

    public function getTransactionStatuses(OrderInterface $order, array $transactions): array
    {
        $results = [];
        $isPaidUsingOrdersApi = $this->mollieHelper->isPaidUsingMollieOrdersApi($order);
        $mollieApi = $this->mollieApiClient->loadByStore((int)$order->getStoreId());
        foreach ($transactions as $mollieTransactionId) {
            $results[$mollieTransactionId] = $isPaidUsingOrdersApi ?
                $this->getOrdersApiStatus($mollieApi, $mollieTransactionId) :
                $this->getPaymentsApiStatus($mollieApi, $mollieTransactionId);
        }

        return $results;
    }

    private function getOrdersApiStatus(\Mollie\Api\MollieApiClient $mollieApiClient, string $transactionId): string
    {
        $order = $mollieApiClient->orders->get($transactionId);

        return $order->status ?? 'unknown';
    }

    private function getPaymentsApiStatus(\Mollie\Api\MollieApiClient $mollieApiClient, string $transactionId): string
    {
        $transaction = $mollieApiClient->payments->get($transactionId);

        return $transaction->status ?? 'unknown';
    }
}
