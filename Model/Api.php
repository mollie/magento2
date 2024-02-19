<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Framework\Module\Manager;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Api\MollieApiClient;

class Api extends MollieApiClient
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var
     */
    public $mollieHelper;
    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        Config $config,
        MollieHelper $mollieHelper,
        Manager $moduleManager
    ) {
        $this->config = $config;
        $this->mollieHelper = $mollieHelper;
        $this->moduleManager = $moduleManager;
        parent::__construct();
    }

    /**
     * @param null $storeId
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function load($storeId = null)
    {
        $this->setApiKey($this->mollieHelper->getApiKey($storeId));
        $this->addVersionString('Magento/' . $this->config->getMagentoVersion());
        $this->addVersionString('MagentoEdition/' . $this->config->getMagentoEdition());
        $this->addVersionString('MollieMagento2/' . $this->mollieHelper->getExtensionVersion());

        if ($this->moduleManager->isEnabled('Hyva_Theme')) {
            $this->addVersionString('HyvaTheme');
        }

        if ($this->moduleManager->isEnabled('Hyva_Checkout')) {
            $this->addVersionString('HyvaCheckout');
        }
    }
}
