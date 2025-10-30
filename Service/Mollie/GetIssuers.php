<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie as MollieModel;

class GetIssuers
{
    public const CACHE_IDENTIFIER_PREFIX = 'mollie_payment_issuers_';

    public function __construct(
        private CacheInterface $cache,
        private SerializerInterface $serializer,
        private MollieModel $mollieModel,
        private Resolver $resolver,
        private Config $config
    ) {}

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
            $type,
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
            60 * 60, // Cache for 1 hour
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
            $this->config->getIssuerListType($method),
        );

        if (!$issuers) {
            return null;
        }

        $output = [];
        foreach ($issuers as $issuer) {
            if (!array_key_exists('image', $issuer)) {
                $output[] = [
                    'name' => $issuer['name'],
                    'code' => $issuer['id'],
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
