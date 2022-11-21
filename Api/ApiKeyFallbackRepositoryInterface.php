<?php declare(strict_types=1);

namespace Mollie\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackSearchResultsInterface;

interface ApiKeyFallbackRepositoryInterface
{
    /**
     * @param int $id
     * @return ApiKeyFallbackInterface
     */
    public function get(int $id);

    /**
     * @param SearchCriteriaInterface $criteria
     * @return ApiKeyFallbackSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param ApiKeyFallbackInterface $entity
     * @return ApiKeyFallbackInterface
     */
    public function save(ApiKeyFallbackInterface $entity);

    /**
     * @param ApiKeyFallbackInterface $entity
     * @return ApiKeyFallbackInterface
     */
    public function delete(ApiKeyFallbackInterface $entity);

    /**
     * @param int $id
     * @return ApiKeyFallbackInterface
     */
    public function deleteById(int $id);
}
