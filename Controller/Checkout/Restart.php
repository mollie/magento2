<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;

class Restart extends Action implements HttpGetActionInterface
{
    /**
     * Success constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        protected Session $checkoutSession,
    ) {
        parent::__construct($context);
    }

    /**
     * Return from loading page after back button.
     */
    public function execute(): ResponseInterface
    {
        $this->messageManager->addNoticeMessage(__('Payment cancelled, please try again.'));
        $this->checkoutSession->restoreQuote();

        return $this->_redirect('checkout/cart');
    }
}
