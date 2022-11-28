<?php

namespace Mollie\Payment\Service\Mollie\ApplePay;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
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

    public function __construct(
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        MollieApiClient $mollieApiClient
    ) {
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->mollieApiClient = $mollieApiClient;
    }

    public function execute(string $validationUrl, string $domain = null): string
    {
        $store = $this->storeManager->getStore();
        $api = $this->mollieApiClient->loadByStore($store->getId());

        if ($domain === null) {
            $domain = parse_url($this->url->getBaseUrl(), PHP_URL_HOST);
        }

        return $api->wallets->requestApplePayPaymentSession(
            $domain,
            $validationUrl
        );
    }
}
