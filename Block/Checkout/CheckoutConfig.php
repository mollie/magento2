<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Mollie\Payment\Config;

class CheckoutConfig extends Template
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function getDefaultSelectedMethod()
    {
        return $this->config->getDefaultSelectedMethod($this->_storeManager->getStore()->getId());
    }
}