<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mollie\Payment\Config;

class CheckoutConfig extends Template
{
    public function __construct(
        Context $context,
        private Config $config,
        private Session $checkoutSession,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function getDefaultSelectedMethod(): ?string
    {
        $method = $this->checkoutSession->getQuote()->getPayment()->getMethod();
        if ($method) {
            return $method;
        }

        return $this->config->getDefaultSelectedMethod(storeId($this->_storeManager->getStore()->getId()));
    }
}
