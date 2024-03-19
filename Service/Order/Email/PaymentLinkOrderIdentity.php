<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Email;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;

class PaymentLinkOrderIdentity extends OrderIdentity
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        parent::__construct($scopeConfig, $storeManager);

        $this->config = $config;
    }

    public function isEnabled()
    {
        return $this->config->paymentLinkUseCustomEmailTemplate();
    }

    public function getTemplateId()
    {
        return $this->config->paymentLinkEmailTemplate();
    }
}
