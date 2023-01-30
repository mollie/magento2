<?php

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TransactionToOrderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface[]
     */
    public function getItems();

    /**
     * @param \Mollie\Payment\Api\Data\TransactionToOrderInterface[] $items
     */
    public function setItems(array $items);
}
