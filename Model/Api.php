<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Module\Manager;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;

class Api extends MollieApiClient
{
    /**
     * @var MollieHelper
     */
    public $mollieHelper;

    public function __construct(
        private Config $config,
        MollieHelper $mollieHelper,
        private Manager $moduleManager,
    ) {
        $this->mollieHelper = $mollieHelper;
        parent::__construct();
    }

    /**
     * @param null $storeId
     * @throws ApiException
     */
    public function load($storeId = null): void
    {
        $this->setApiKey($this->mollieHelper->getApiKey($storeId));
        $this->addVersionString('Magento/' . $this->config->getMagentoVersion());
        $this->addVersionString('MagentoEdition/' . $this->config->getMagentoEdition());
        $this->addVersionString('MollieMagento2/' . $this->config->getVersion());

        if ($this->moduleManager->isEnabled('Hyva_Theme')) {
            $this->addVersionString('HyvaTheme');
        }

        if ($this->moduleManager->isEnabled('Hyva_Checkout')) {
            $this->addVersionString('HyvaCheckout');
        }
    }
}
