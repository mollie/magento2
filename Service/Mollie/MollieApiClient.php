<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Mollie\Api\MollieApiClientFactory;
use Mollie\Payment\Config;

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
     * @var MollieApiClientFactory
     */
    private $mollieApiClientFactory;

    public function __construct(
        Config $config,
        MollieApiClientFactory $mollieApiClientFactory
    ) {
        $this->config = $config;
        $this->mollieApiClientFactory = $mollieApiClientFactory;
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

        $mollieApiClient = $this->mollieApiClientFactory->create();
        $mollieApiClient->setApiKey($apiKey);
        $mollieApiClient->addVersionString('Magento/' . $this->config->getMagentoVersion());
        $mollieApiClient->addVersionString('MollieMagento2/' . $this->config->getVersion());
        $this->instances[$apiKey] = $mollieApiClient;

        return $mollieApiClient;
    }
}
