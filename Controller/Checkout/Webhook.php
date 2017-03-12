<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;

class Webhook extends Action
{

    protected $checkoutSession;
    protected $resultFactory;
    protected $paymentHelper;
    protected $mollieModel;
    protected $mollieHelper;

    /**
     * Webhook constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param MollieModel   $mollieModel
     * @param MollieHelper  $mollieHelper
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
        $this->resultFactory = $context->getResultFactory();
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        parent::__construct($context);
    }

    /**
     * Mollie webhook
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!empty($params['testByMollie'])) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents('OK', true);
            return;
        }

        if (!empty($params['id'])) {
            try {
                $orderId = $this->mollieModel->getOrderIdByTransactionId($params['id']);
                if ($orderId) {
                    $this->mollieModel->processTransaction($orderId, 'webhook');
                }
            } catch (\Exception $e) {
                $this->mollieHelper->addTolog('error', $e->getMessage());
            }
        }
    }
}
