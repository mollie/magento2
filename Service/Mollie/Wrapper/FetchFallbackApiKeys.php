<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Mollie\Payment\Api\ApiKeyFallbackRepositoryInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;
use Mollie\Payment\Config;

class FetchFallbackApiKeys
{
    public function __construct(
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private SortOrderFactory $sortOrderFactory,
        private EncryptorInterface $encryptor,
        private Config $config,
        private ApiKeyFallbackRepositoryInterface $apiKeyFallbackRepository
    ) {}

    public function retrieve(): array
    {
        /** @var SortOrder $sortOrder */
        $sortOrder = $this->sortOrderFactory->create();
        $sortOrder->setField('created_at');
        $sortOrder->setDirection(SortOrder::SORT_DESC);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('mode', $this->config->isProductionMode() ? 'live' : 'test')
            ->addSortOrder($sortOrder)
            ->create();

        $result = $this->apiKeyFallbackRepository->getList($searchCriteria);

        return array_map(function (ApiKeyFallbackInterface $fallback) {
            return $this->encryptor->decrypt($fallback->getApikey());
        }, $result->getItems());
    }
}
