<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\ApplePay;

use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Mollie\Payment\Config;

class Certificate
{
    public const CACHE_IDENTIFIER_PREFIX = 'mollie_payment_apple_pay_certificate';

    public function __construct(
        private Config $config,
        private CacheInterface $cache,
        private ClientInterface $client
    ) {}

    /**
     * @return string
     * @throws Exception
     */
    public function execute(): string
    {
        $identifier = static::CACHE_IDENTIFIER_PREFIX;
        $result = $this->cache->load($identifier);
        if ($result) {
            return $result;
        }

        $this->config->addToLog('Fetching Apple Pay certificate from www.mollie.com', []);
        $certificate = $this->fetchCertificate();

        $this->cache->save(
            $certificate,
            $identifier,
            ['mollie_payment', 'mollie_payment_apple_pay_certificate'],
            7 * 24 * 60 * 60, // Cache for 1 week
        );

        return $certificate;
    }

    private function fetchCertificate(): string
    {
        $this->client->get('https://www.mollie.com/.well-known/apple-developer-merchantid-domain-association');

        if ($this->client->getStatus() !== 200) {
            throw new LocalizedException(__('Unable to retrieve Apple Pay certificate from www.mollie.com'));
        }

        return $this->client->getBody();
    }
}
