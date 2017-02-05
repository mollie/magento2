<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Redirect extends Action
{

    protected $checkoutSession;
    protected $logger;
    protected $paymentHelper;
    protected $mollieHelper;

    /**
     * Redirect constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param MollieHelper  $mollieHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        MollieHelper $mollieHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Mollie after placing order
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
            if ($methodInstance instanceof \Mollie\Payment\Model\Mollie) {
                $issuer = $this->getRequest()->getParam('issuer');
                $redirectUrl = $methodInstance->startTransaction($order, $issuer);
                $this->_redirect($redirectUrl);
            } else {
                $msg = __('Paymentmethod not found.');
                $this->messageManager->addError($msg);
                $this->mollieHelper->addTolog('error', $msg);
                $this->checkoutSession->restoreQuote();
                $this->_redirect('checkout/cart');
            }
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __($e->getMessage()));
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
