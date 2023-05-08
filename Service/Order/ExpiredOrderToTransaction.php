<?php

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Model\TransactionToOrderRepository;

class ExpiredOrderToTransaction
{
    /**
     * @var TransactionToOrderRepository
     */
    private $transactionToOrderRepository;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    public function __construct(
        TransactionToOrderRepository $transactionToOrderRepository,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory
    ) {
        $this->transactionToOrderRepository = $transactionToOrderRepository;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
    }

    public function hasMultipleTransactions(OrderInterface $order): bool
    {
        $criteria = $this->criteriaBuilderFactory->create();
        $criteria->addFilter('skipped', '0');
        $criteria->addFilter('order_id', $order->getEntityId());

        $result = $this->transactionToOrderRepository->getList($criteria->create());

        return $result->getTotalCount() > 1;
    }

    public function getByTransactionId(string $transactionId): TransactionToOrderInterface
    {
        $criteria = $this->criteriaBuilderFactory->create();
        $criteria->addFilter('transaction_id', $transactionId);

        $result = $this->transactionToOrderRepository->getList($criteria->create());

        $items = $result->getItems();

        if (empty($items)) {
            throw new NoSuchEntityException(__("Transaction with ID %1 not found", $transactionId));
        }

        return array_shift($items);
    }

    /**
     * A transaction can be skipped if there are multiple transactions for a single order, and this transaction
     * is expired. In that case, we don't want to cancel the order, but we do want to mark the transaction as skipped.
     * When the next transaction is also expired, and there are no other transactions left, we will cancel the order.
     */
    public function markTransactionAsSkipped(string $transactionId): void
    {
        $transaction = $this->getByTransactionId($transactionId);
        $transaction->setSkipped(true);

        $this->transactionToOrderRepository->save($transaction);
    }
}
