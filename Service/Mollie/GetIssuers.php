<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Model\Mollie as MollieModel;

class GetIssuers
{
    const CACHE_IDENTIFIER_PREFIX = 'mollie_payment_issuers_';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MollieModel
     */
    private $mollieModel;

    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        MollieModel $mollieModel
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->mollieModel = $mollieModel;
    }

    /**
     * @param MollieApiClient $mollieApi
     * @param string $method
     * @param string $type On of: dropdown, radio, none
     * @return array
     */
    public function execute(MollieApiClient $mollieApi, $method, $type)
    {
        $identifier = static::CACHE_IDENTIFIER_PREFIX . $method;
        $result = $this->cache->load($identifier);
        if ($result) {
            return $this->serializer->unserialize($result);
        }

        $result = $this->mollieModel->getIssuers(
            $mollieApi,
            $method,
            $type
        );

        $this->cache->save(
            $this->serializer->serialize($result),
            $identifier,
            ['mollie_payment', 'mollie_payment_issuers'],
            60 * 60 // Cache for 1 hour
        );

        return $result;
    }
}