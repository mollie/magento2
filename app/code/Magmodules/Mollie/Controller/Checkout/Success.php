<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Controller\Checkout;

use Magmodules\Mollie\Model\Mollie as MollieModel;
use Magmodules\Mollie\Helper\General as MollieHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Success extends Action
{

    protected $checkoutSession;
    protected $logger;
    protected $paymentHelper;
    protected $mollieModel;
    protected $mollieHelper;

    /**
     * Success constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param MollieModel $mollieModel
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * Return from mollie after payment
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['order_id'])) {
            $this->mollieHelper->addTolog('error', __('Invalid return, missing order id.'));
            $this->messageManager->addNoticeMessage(__('Invalid return from Mollie.'));
            $this->_redirect('checkout/cart');
        }

        try {
            $status = $this->mollieModel->processTransaction($params['order_id'], 'success');
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e);
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));
            $this->_redirect('checkout/cart');
        }

        if (isset($status['success'])) {
            $this->checkoutSession->start();
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->checkoutSession->restoreQuote();
            $this->messageManager->addNoticeMessage(__('Something went wrong.'));
            $this->_redirect('checkout/cart');
        }
    }
}
