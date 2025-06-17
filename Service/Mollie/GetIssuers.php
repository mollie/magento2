<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Locale\Resolver;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
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

    /**
     * @var Resolver
     */
    private $resolver;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        MollieModel $mollieModel,
        Resolver $resolver,
        Config $config
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->mollieModel = $mollieModel;
        $this->resolver = $resolver;
        $this->config = $config;
    }

    /**
     * @param string $method
     * @param string $type On of: dropdown, radio, none
     * @return array|null
     */
    public function execute(string $method, string $type): ?array
    {
        $identifier = static::CACHE_IDENTIFIER_PREFIX . $method . $type . $this->resolver->getLocale();
        $result = $this->cache->load($identifier);
        if ($result) {
            return $this->serializer->unserialize($result);
        }

        $result = $this->mollieModel->getIssuers(
            $method,
            $type
        );

        // If the result is false-y, don't cache it.
        if (!$result) {
            return $result;
        }

        // $result will be a nested stdClass, this converts it on all levels to an array.
        $result = json_decode(json_encode($result), true);

        $this->cache->save(
            $this->serializer->serialize($result),
            $identifier,
            ['mollie_payment', 'mollie_payment_issuers'],
            60 * 60 // Cache for 1 hour
        );

        return $result;
    }

    /**
     * @param int|string|null $storeId
     * @param string $method
     * @return array|null
     */
    public function getForGraphql($storeId, string $method): ?array
    {
        $issuers = $this->execute(
            $method,
            $this->config->getIssuerListType($method)
        );

        if (!$issuers) {
            return null;
        }

        $output = [];
        foreach ($issuers as $issuer) {
            if (!array_key_exists('image', $issuer)) {
                $output[] = [
                    'name' => $issuer['name'],
                    'code' => $issuer['id']
                ];
                continue;
            }

            $output[] = [
                'name' => $issuer['name'],
                'code' => $issuer['id'],
                'image' => $issuer['image']['size2x'],
                'svg' => $issuer['image']['svg'],
            ];
        }

        return $output;
    }
}
