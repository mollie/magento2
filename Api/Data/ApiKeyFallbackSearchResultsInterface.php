<?php declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ApiKeyFallbackSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get ApiKeyFallback list.
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Mollie\Payment\Api\Data\ApiKeyFallbackInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
