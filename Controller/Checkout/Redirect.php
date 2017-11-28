<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Result\PageFactory;

class Redirect extends Action
{

    protected $checkoutSession;
    protected $resultPageFactory;
    protected $paymentHelper;
    protected $mollieHelper;

    /**
     * Redirect constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PageFactory   $resultPageFactory
     * @param PaymentHelper $paymentHelper
     * @param MollieHelper  $mollieHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper,
        MollieHelper $mollieHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultPageFactory = $resultPageFactory;
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

            if (!$order) {
                $msg = __('Order not found.');
                $this->mollieHelper->addTolog('error', $msg);
                $this->_redirect('checkout/cart');
            }

            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
            if ($methodInstance instanceof \Mollie\Payment\Model\Mollie) {
                $storeId = $order->getStoreId();
                $redirectUrl = $methodInstance->startTransaction($order);
                $this->mollieHelper->addTolog('request', $redirectUrl);
                if ($this->mollieHelper->useLoadingScreen($storeId)) {
                    $resultPage = $this->resultPageFactory->create();
                    $resultPage->getLayout()->initMessages();
                    $resultPage->getLayout()->getBlock('mollie_loading')->setMollieRedirect($redirectUrl);
                    return $resultPage;
                } else {
                    $this->getResponse()->setRedirect($redirectUrl);
                }
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
