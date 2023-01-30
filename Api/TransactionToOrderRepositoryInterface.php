<?php

declare(strict_types=1);

namespace Mollie\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderSearchResultsInterface;

interface TransactionToOrderRepositoryInterface
{
    /**
     * @param int $id
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function get(int $id): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
      * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
      * @return \Mollie\Payment\Api\Data\TransactionToOrderSearchResultsInterface
      */
    public function getList(SearchCriteriaInterface $criteria): \Mollie\Payment\Api\Data\TransactionToOrderSearchResultsInterface;

    /**
     * @param \Mollie\Payment\Api\Data\TransactionToOrderInterface $entity
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function save(TransactionToOrderInterface $entity): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
      * @param \Mollie\Payment\Api\Data\TransactionToOrderInterface $entity
      * @return bool
      */
    public function delete(TransactionToOrderInterface $entity): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;
}
