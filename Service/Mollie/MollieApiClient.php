<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\Wrapper\FetchFallbackApiKeys;
use Mollie\Payment\Service\Mollie\Wrapper\MollieApiClientFallbackWrapper;
use Mollie\Payment\Service\Mollie\Wrapper\MollieApiClientFallbackWrapperFactory;

class MollieApiClient
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Mollie\Api\MollieApiClient[]
     */
    private $instances = [];

    /**
     * @var MollieApiClientFallbackWrapperFactory
     */
    private $mollieApiClientWrapperFactory;

    /**
     * @var FetchFallbackApiKeys
     */
    private $fetchFallbackApiKeys;

    public function __construct(
        Config $config,
        MollieApiClientFallbackWrapperFactory $mollieApiClientWrapperFactory,
        FetchFallbackApiKeys $fetchFallbackApiKeys
    ) {
        $this->config = $config;
        $this->mollieApiClientWrapperFactory = $mollieApiClientWrapperFactory;
        $this->fetchFallbackApiKeys = $fetchFallbackApiKeys;
    }

    public function loadByStore(int $storeId = null): \Mollie\Api\MollieApiClient
    {
        if (!class_exists('Mollie\Api\MollieApiClient')) {
            throw new LocalizedException(__('Class Mollie\Api\MollieApiClient does not exist'));
        }

        return $this->loadByApiKey($this->config->getApiKey($storeId));
    }

    public function loadByApiKey(string $apiKey): \Mollie\Api\MollieApiClient
    {
        if (isset($this->instances[$apiKey])) {
            return $this->instances[$apiKey];
        }

        /** @var MollieApiClientFallbackWrapper $mollieApiClientWrapper */
        $mollieApiClient = $this->mollieApiClientWrapperFactory->create();
        $mollieApiClient->orders->setFallbackApiKeysInstance($this->fetchFallbackApiKeys);
        $mollieApiClient->payments->setFallbackApiKeysInstance($this->fetchFallbackApiKeys);

        $mollieApiClient->setApiKey($apiKey);
        $mollieApiClient->addVersionString('Magento/' . $this->config->getMagentoVersion());
        $mollieApiClient->addVersionString('MagentoEdition/' . $this->config->getMagentoEdition());
        $mollieApiClient->addVersionString('MollieMagento2/' . $this->config->getVersion());
        $this->instances[$apiKey] = $mollieApiClient;

        return $mollieApiClient;
    }
}
