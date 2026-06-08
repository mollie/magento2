<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\Wrapper\FetchFallbackApiKeys;
use Mollie\Payment\Service\Mollie\Wrapper\MollieApiClientFallbackWrapper;
use Mollie\Payment\Service\Mollie\Wrapper\MollieApiClientFallbackWrapperFactory;

class MollieApiClient
{
    /**
     * @var \Mollie\Api\MollieApiClient[]
     */
    private $instances = [];

    public function __construct(
        private Config $config,
        private MollieApiClientFallbackWrapperFactory $mollieApiClientWrapperFactory,
        private FetchFallbackApiKeys $fetchFallbackApiKeys,
        private Manager $moduleManager
    ) {}

    public function loadByStore(?int $storeId = null): \Mollie\Api\MollieApiClient
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

        /** @var MollieApiClientFallbackWrapper $mollieApiClient */
        $mollieApiClient = $this->mollieApiClientWrapperFactory->create();
        $mollieApiClient->setFallbackApiKeysInstance($this->fetchFallbackApiKeys);

        $mollieApiClient->setApiKey($apiKey);
        $mollieApiClient->addVersionString('Magento/' . $this->config->getMagentoVersion());
        $mollieApiClient->addVersionString('MagentoEdition/' . $this->config->getMagentoEdition());
        $mollieApiClient->addVersionString('MollieMagento2/' . $this->config->getVersion());

        if ($this->moduleManager->isEnabled('Hyva_Theme')) {
            $mollieApiClient->addVersionString('HyvaTheme');
        }

        if ($this->moduleManager->isEnabled('Hyva_Checkout')) {
            $mollieApiClient->addVersionString('HyvaCheckout');
        }

        $this->instances[$apiKey] = $mollieApiClient;

        return $mollieApiClient;
    }
}
