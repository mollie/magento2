<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\ApplePay;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class Validation
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        MollieApiClient $mollieApiClient,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->mollieApiClient = $mollieApiClient;
        $this->config = $config;
    }

    public function execute(string $validationUrl, ?string $domain = null): string
    {
        $store = $this->storeManager->getStore();
        $api = $this->mollieApiClient->loadByApiKey($this->getLiveApiKey((int)$store->getId()));

        if ($domain === null) {
            $domain = parse_url($this->url->getBaseUrl(), PHP_URL_HOST);
        }

        return $api->wallets->requestApplePayPaymentSession(
            $domain,
            $validationUrl
        );
    }

    private function getLiveApiKey(int $storeId): string
    {
        $liveApikey = $this->config->getLiveApiKey($storeId);
        if (!$liveApikey) {
            throw new \Exception(__('For Apple Pay the live API key is required, even when in test mode'));
        }

        return $liveApikey;
    }
}
