<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

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

    public function __construct(
        Config $config,
        MollieHelper $mollieHelper
    ) {
        $this->config = $config;
        $this->mollieHelper = $mollieHelper;
        parent::__construct();
    }

    /**
     * @param null $storeId
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function load($storeId = null)
    {
        $this->setApiKey($this->mollieHelper->getApiKey($storeId));
        $this->addVersionString('Magento/' . $this->mollieHelper->getMagentoVersion());
        $this->addVersionString('MollieMagento2/' . $this->mollieHelper->getExtensionVersion());
    }
}
