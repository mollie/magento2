<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\ApplePay;

use Laminas\Uri\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class Validation
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private UrlInterface $url,
        private MollieApiClient $mollieApiClient,
        private Config $config,
        private Http $http
    ) {}

    public function execute(string $validationUrl, ?string $domain = null): string
    {
        $store = $this->storeManager->getStore();
        $api = $this->mollieApiClient->loadByApiKey($this->getLiveApiKey((int) $store->getId()));

        if ($domain === null) {
            $domain = $this->http->parse($this->url->getBaseUrl())->getHost();
        }

        return $api->wallets->requestApplePayPaymentSession(
            $domain,
            $validationUrl,
        );
    }

    private function getLiveApiKey(int $storeId): string
    {
        $liveApikey = $this->config->getLiveApiKey($storeId);
        if (!$liveApikey) {
            throw new LocalizedException(__('For Apple Pay the live API key is required, even when in test mode'));
        }

        return $liveApikey;
    }
}
