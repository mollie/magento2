<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ApiKeyFallbackSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get ApiKeyFallback list.
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackInterface[]
     */
    public function getItems(): array;

    /**
     * Set id list.
     * @param \Mollie\Payment\Api\Data\ApiKeyFallbackInterface[] $items
     * @return $this
     */
    public function setItems(array $items): SearchResultsInterface;
}
