<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\Order\CreateCreditmemoFromRefund;
use Mollie\Payment\Service\Order\OrderCommentHistory;

class HandleExternalRefunds implements PaymentProcessorInterface
{
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CreateCreditmemoFromRefund $createCreditmemoFromRefund,
        private readonly Config $config,
        private readonly OrderCommentHistory $orderCommentHistory,
        private readonly OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        $unprocessedRefunds = $this->getUnprocessedRefunds($magentoOrder, $molliePayment);

        if ($unprocessedRefunds === []) {
            return $response;
        }

        if (!$this->config->createCreditmemoForExternalRefunds(storeId($magentoOrder->getStoreId()))) {
            $this->addCommentsForSkippedRefunds($magentoOrder, $unprocessedRefunds);

            return $response;
        }

        $invoices = $magentoOrder->getInvoiceCollection();
        /** @var InvoiceInterface $invoice */
        $invoice = $invoices->getFirstItem();
        foreach ($unprocessedRefunds as $refund) {
            $this->createCreditmemoFromRefund->execute($magentoOrder, $invoice, $refund);
        }

        return $response;
    }

    /**
     * @return Refund[]
     */
    private function getUnprocessedRefunds(OrderInterface $order, Payment $molliePayment): array
    {
        $transactions = $this->getTransactions($order);

        return array_values(array_filter(
            iterator_to_array($molliePayment->refunds()),
            fn (Refund $refund): bool => !in_array($refund->id, $transactions, true),
        ));
    }

    /**
     * @param Refund[] $refunds
     */
    private function addCommentsForSkippedRefunds(OrderInterface $order, array $refunds): void
    {
        $newRefunds = array_filter(
            $refunds,
            fn (Refund $refund): bool => !$this->isRefundInOrderHistory($order, $refund),
        );

        foreach ($newRefunds as $refund) {
            $this->orderCommentHistory->add(
                $order,
                __(
                    'Refund %1 of %2 was made outside Magento, possibly through the Mollie Dashboard. No credit memo was created.',
                    $refund->id,
                    $order->getOrderCurrency()->formatTxt($refund->amount->value),
                ),
            );
        }
    }

    private function isRefundInOrderHistory(OrderInterface $order, Refund $refund): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_id', $order->getEntityId())
            ->addFilter('comment', '%' . $refund->id . '%', 'like')
            ->setPageSize(1)
            ->create();

        return $this->orderStatusHistoryRepository->getList($searchCriteria)->getTotalCount() > 0;
    }

    /**
     * @return string[]
     */
    private function getTransactions(OrderInterface $order): array
    {
        $filters[] = $this->filterBuilder->setField('payment_id')
            ->setValue($order->getPayment()->getId())
            ->create();

        $filters[] = $this->filterBuilder->setField('order_id')
            ->setValue($order->getId())
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
            ->create();

        $transactionList = $this->transactionRepository->getList($searchCriteria);

        $output = [];
        foreach ($transactionList->getItems() as $transaction) {
            $output[] = $transaction->getTxnId();
        }

        return $output;
    }
}
