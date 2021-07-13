<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

/**
 * Class Success
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Restart extends Action
{

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * Return from loading page after back button.
     */
    public function execute()
    {
        $this->messageManager->addNoticeMessage(__('Payment cancelled, please try again.'));
        $this->checkoutSession->restoreQuote();
        return $this->_redirect('checkout/cart');
    }
}
