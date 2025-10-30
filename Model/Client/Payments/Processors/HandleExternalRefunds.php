<?php

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\Order\CreateCreditmemoFromRefund;

class HandleExternalRefunds implements PaymentProcessorInterface
{
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CreateCreditmemoFromRefund $createCreditmemoFromRefund,
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        $transactions = $this->getTransactions($magentoOrder);
        $refunds = $molliePayment->refunds();

        $unprocessedRefunds = [];
        foreach ($refunds as $refund) {
            if (!in_array($refund->id, $transactions)) {
                $unprocessedRefunds[] = $refund;
            }
        }

        if ($unprocessedRefunds === []) {
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
