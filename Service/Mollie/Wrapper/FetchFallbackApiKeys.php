<?php

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
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderFactory
     */
    private $sortOrderFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ApiKeyFallbackRepositoryInterface
     */
    private $apiKeyFallbackRepository;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderFactory $sortOrderFactory,
        EncryptorInterface $encryptor,
        Config $config,
        ApiKeyFallbackRepositoryInterface $apiKeyFallbackRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderFactory = $sortOrderFactory;
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->apiKeyFallbackRepository = $apiKeyFallbackRepository;
    }

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
