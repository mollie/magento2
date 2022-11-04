<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Mollie\Payment\Config;

class CheckoutConfig extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        Template\Context $context,
        Config $config,
        Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
    }

    public function getDefaultSelectedMethod(): ?string
    {
        $method = $this->checkoutSession->getQuote()->getPayment()->getMethod();
        if ($method) {
            return $method;
        }

        return $this->config->getDefaultSelectedMethod($this->_storeManager->getStore()->getId());
    }
}
