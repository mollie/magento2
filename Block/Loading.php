<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block;

use Magento\Checkout\Model\Session;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class Loading
 *
 * @package Mollie\Payment\Block
 * @method string getMollieRedirect()
 * @deprecated since version 2.18.0
 */
class Loading extends Template
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * Loading constructor.
     *
     * @param Context      $context
     * @param Session      $checkoutSession
     * @param MollieHelper $mollieHelper
     * @param array        $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MollieHelper $mollieHelper,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->mollieHelper = $mollieHelper;
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

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->mollieHelper->getRestartUrl();
    }
}
