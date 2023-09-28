<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mollie\Payment\Api\ApiKeyFallbackRepositoryInterface;

class EncryptFallbackKeys implements DataPatchInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ApiKeyFallbackRepositoryInterface
     */
    private $apiKeyFallbackRepository;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ApiKeyFallbackRepositoryInterface $apiKeyFallbackRepository,
        EncryptorInterface $encryptor
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->apiKeyFallbackRepository = $apiKeyFallbackRepository;
        $this->encryptor = $encryptor;
    }

    public function apply()
    {
        $criteria = $this->searchCriteriaBuilder->create();
        $list = $this->apiKeyFallbackRepository->getList($criteria);

        if ($list->getTotalCount() === 0) {
            return $this;
        }

        foreach ($list->getItems() as $item) {
            $start = substr($item->getApiKey(), 0, 4);
            if (!in_array($start, ['live', 'test'])) {
                continue;
            }

            $item->setApiKey($this->encryptor->encrypt($item->getApiKey()));
            $this->apiKeyFallbackRepository->save($item);
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
