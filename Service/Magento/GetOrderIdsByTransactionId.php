<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\ResourceModel\TransactionToOrder as TransactionToOrderResource;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\LegacyOrderTransactionId;
use Mollie\Payment\Service\Mollie\Order\LinkTransactionToOrder;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Service\Order\OrderCommentHistory;

class GetOrderIdsByTransactionId
{
    private const SKIPPABLE_STATUSES = [PaymentStatus::CANCELED, PaymentStatus::FAILED];

    public function __construct(
        private readonly Config $config,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        private readonly MollieApiClient $mollieApiClient,
        private readonly LinkTransactionToOrder $linkTransactionToOrder,
        private readonly OrderResource $orderResource,
        private readonly TransactionToOrderResource $transactionToOrderResource,
        private readonly OrderCommentHistory $orderCommentHistory,
        private readonly LegacyOrderTransactionId $legacyOrderTransactionId,
        private readonly ExpiredOrderToTransaction $expiredOrderToTransaction,
        private readonly PaymentTokenRepositoryInterface $paymentTokenRepository,
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

        $existingTransactionId = (string) $order->getMollieTransactionId();
        if ($existingTransactionId === '') {
            $this->linkTransactionToOrder->execute($transactionId, $order);
            $this->orderResource->saveAttribute($order, 'mollie_transaction_id');

            return [$orderId];
        }

        if ($this->legacyOrderTransactionId->matches($existingTransactionId)) {
            return [];
        }

        if (!$this->hasPaymentTokenOfOrder($payment, $order)) {
            $this->config->addTolog('debug', __(
                'Transaction %1 is not linked to order %2: the payment token in its metadata is not of this order.',
                $transactionId,
                $order->getIncrementId(),
            ));

            return [];
        }

        $this->linkExtraTransaction($existingTransactionId, $transactionId, $order);
        $this->skipWhenFinallyUnsuccessful($existingTransactionId, $payment);

        return [$orderId];
    }

    /**
     * The webhook is public, and other integrations on the same Mollie account can also use metadata.order_id.
     * The payment token is random and stored with the order, so it proves the payment was created for this order.
     */
    private function hasPaymentTokenOfOrder(Payment $payment, OrderInterface $order): bool
    {
        $token = (string) ($payment->metadata->payment_token ?? '');
        if ($token === '') {
            return false;
        }

        return (int) $this->paymentTokenRepository->getByToken($token)?->getOrderId() === (int) $order->getEntityId();
    }

    /**
     * A duplicate that is already canceled or failed can never pay the order. Skip it, so ExpiredStatusProcessor
     * still cancels the order when its own payment expires. An expired duplicate is left to ExpiredStatusProcessor,
     * which skips it itself while another payment of the order can still be paid.
     */
    private function skipWhenFinallyUnsuccessful(string $existingTransactionId, Payment $payment): void
    {
        if ($payment->id === $existingTransactionId || !in_array($payment->status, self::SKIPPABLE_STATUSES, true)) {
            return;
        }

        $this->expiredOrderToTransaction->markTransactionAsSkipped($payment->id);
    }

    /**
     * An order can have more than one Mollie payment, for example when the response of a create call was lost and the
     * call was retried. Link every payment to the order so GetTransactionId can select the paid one, but keep the
     * transaction id on the order itself unchanged.
     */
    private function linkExtraTransaction(
        string $existingTransactionId,
        string $transactionId,
        OrderInterface $order,
    ): void {
        $this->transactionToOrderResource->link($existingTransactionId, (int) $order->getEntityId());
        $this->transactionToOrderResource->link($transactionId, (int) $order->getEntityId());

        if ($existingTransactionId === $transactionId) {
            return;
        }

        $this->config->addTolog('info', __(
            'Linked transaction %1 to order %2 by its metadata. The order already has transaction %3.',
            $transactionId,
            $order->getIncrementId(),
            $existingTransactionId,
        ));

        $this->orderCommentHistory->add($order, __(
            'Mollie payment %1 was not linked to this order. It is now linked through the order id in its metadata.',
            $transactionId,
        ));
    }
}
