<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderManagementInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;

class GetTransactionId
{
    private const UNSUCCESSFUL_STATUSES = [PaymentStatus::CANCELED, PaymentStatus::FAILED];
    private const OPEN_STATUSES = [PaymentStatus::OPEN, PaymentStatus::PENDING];

    public function __construct(
        private readonly TransactionToOrderManagementInterface $transactionToOrderManagement,
        private readonly MollieApiClient $mollieApiClient,
        private readonly Config $config,
        private readonly ExpiredOrderToTransaction $expiredOrderToTransaction,
    ) {}

    /**
     * Sometimes an order gets multiple transactions. If that's the case, this code will return the first
     * transaction that has the status 'paid' or 'authorized'. This is to prevent issues where one transaction
     * is 'paid', but the other 'pending' transaction transitions to 'canceled'. This code prevents the order
     * to get canceled in that case.
     *
     *
     * @param OrderInterface $order
     * @return string|null
     */
    public function forOrder(OrderInterface $order): ?string
    {
        $transactions = array_map(function (TransactionToOrderInterface $transactionToOrder): ?string {
            return $transactionToOrder->getTransactionId();
        }, $this->transactionToOrderManagement->getForOrder((int) $order->getEntityId()));

        if (!$transactions) {
            return $order->getMollieTransactionId();
        }

        if (count($transactions) === 1) {
            return reset($transactions);
        }

        $this->config->addToLog('warning', [
            'Multiple transactions found for order #' . $order->getIncrementId() . '/' . $order->getEntityId(),
            $transactions,
        ]);

        $statuses = $this->getTransactionStatuses($order, $transactions);
        foreach ($statuses as $transactionId => $status) {
            if (in_array($status, [PaymentStatus::PAID, PaymentStatus::AUTHORIZED], true)) {
                $order->setMollieTransactionId($transactionId);

                return $transactionId;
            }
        }

        $openTransactionId = $this->getOpenTransactionInsteadOfUnsuccessfulOne($order, $statuses);
        if ($openTransactionId !== null) {
            $this->expiredOrderToTransaction->markTransactionAsSkipped((string) $order->getMollieTransactionId());
            $order->setMollieTransactionId($openTransactionId);

            return $openTransactionId;
        }

        return $order->getMollieTransactionId();
    }

    /**
     * A canceled or failed duplicate payment must not cancel the order while another payment for it can still be
     * paid. The unsuccessful payment is skipped, so ExpiredStatusProcessor cancels the order when the open one
     * expires. Expired payments are left out: ExpiredStatusProcessor skips them one by one until the last one expires.
     *
     * @param array<string, string> $statuses
     */
    private function getOpenTransactionInsteadOfUnsuccessfulOne(OrderInterface $order, array $statuses): ?string
    {
        $currentStatus = $statuses[(string) $order->getMollieTransactionId()] ?? null;
        if (!in_array($currentStatus, self::UNSUCCESSFUL_STATUSES, true)) {
            return null;
        }

        $openTransactionIds = array_keys(array_filter(
            $statuses,
            fn (string $status): bool => in_array($status, self::OPEN_STATUSES, true),
        ));

        return $openTransactionIds[0] ?? null;
    }

    /**
     * @param string[] $transactions
     * @return array<string, string>
     */
    public function getTransactionStatuses(OrderInterface $order, array $transactions): array
    {
        $results = [];
        $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));
        foreach ($transactions as $mollieTransactionId) {
            $results[$mollieTransactionId] = $this->getPaymentsApiStatus($mollieApi, $mollieTransactionId);
        }

        return $results;
    }

    private function getPaymentsApiStatus(\Mollie\Api\MollieApiClient $mollieApiClient, string $transactionId): string
    {
        $transaction = $mollieApiClient->payments->get($transactionId);

        return $transaction->status;
    }
}
