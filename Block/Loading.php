<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block;
 
use Magento\Checkout\Model\Session;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;

class Loading extends Template
{
    protected $checkoutSession;

    /**
     * Loading constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * Disable caching of block.
     *
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        $orderId = $this->checkoutSession->getLastRealOrder()->getId();
        if ($this->checkoutSession->getMollieRedirect() == $orderId) {
            $this->checkoutSession->restoreQuote();
            return $this->getUrl('checkout/') . '#payment';
        } else {
            $this->checkoutSession->setMollieRedirect($orderId);
            return $this->getMollieRedirect();
        }
    }
}
