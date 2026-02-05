<?php

namespace Mollie\Payment\Service\Magento;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\LinkTransactionToOrder;

class GetOrderIdsByTransactionId
{
    public function __construct(
        private readonly Config $config,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        private readonly MollieApiClient $mollieApiClient,
        private readonly LinkTransactionToOrder $linkTransactionToOrder,
    ) {
    }

    public function execute(string $transactionId): array
    {
        $orderIds = $this->getByTransactionToOrderRepository($transactionId);

        if ($orderIds === []) {
            $orderIds = $this->tryToMatchByMetadata($transactionId);
        }

        if ($orderIds === []) {
            $this->config->addTolog('error', __('No order(s) found for transaction id %1', $transactionId));
        }

        return $orderIds;
    }

    private function getByTransactionToOrderRepository(string $transactionId): array
    {
        $this->searchCriteriaBuilder->addFilter('transaction_id', $transactionId);
        $orders = $this->transactionToOrderRepository->getList($this->searchCriteriaBuilder->create());

        if (!$orders->getTotalCount()) {
            return [];
        }

        return array_map(function (TransactionToOrderInterface $transactionToOrder): ?int {
            return $transactionToOrder->getOrderId();
        }, $orders->getItems());
    }

    private function tryToMatchByMetadata(string $transactionId): array
    {
        $payment = $this->mollieApiClient->loadByStore()->payments->get($transactionId);

        $orderId = $payment->metadata->order_id ?? null;
        if ($orderId === null) {
            return [];
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException) {
            $this->config->addTolog('error', __(
                'Transaction %1 has order_id %1 in the metadata, but this order does not exists',
                $transactionId,
                $orderId
            ));
            return [];
        }

        // If there is already an transaction linked to this order, we don't need to link it again.'
        if ($order->getMollieTransactionId()) {
            $this->config->addTolog('debug', __(
                'Order %1 has already transaction %1 linked to it. Skipping',
                $orderId,
                $transactionId
            ));

            return [];
        }

        $this->linkTransactionToOrder->execute($transactionId, $order);
        $this->orderRepository->save($order);

        return [$orderId];
    }
}
