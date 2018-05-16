<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Api\MollieApiClient;

class Api extends MollieApiClient
{

    /**
     * @var
     */
    public $mollieHelper;

    /**
     * Api constructor.
     *
     * @param MollieHelper $mollieHelper
     *
     * @throws \Mollie\Api\Exceptions\IncompatiblePlatform
     */
    public function __construct(
        MollieHelper $mollieHelper
    ) {
        $this->mollieHelper = $mollieHelper;
        parent::__construct();
    }

    /**
     * @param $apiKey
     *
     * @return MollieApiClient
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function load($apiKey)
    {
        $mollieApiClient = new MollieApiClient();
        $mollieApiClient->setApiKey($apiKey);
        $mollieApiClient->addVersionString('Magento/' . $this->mollieHelper->getMagentoVersion());
        $mollieApiClient->addVersionString('MollieMagento2/' . $this->mollieHelper->getExtensionVersion());
        return $mollieApiClient;
    }
}