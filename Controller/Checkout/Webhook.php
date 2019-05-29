<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Webhook
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Webhook extends Action
{

    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var MollieModel
     */
    protected $mollieModel;
    /**
     * @var MollieHelper
     */
    protected $mollieHelper;

    /**
     * Webhook constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param MollieModel   $mollieModel
     * @param MollieHelper  $mollieHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
        $this->checkoutSession = $checkoutSession;
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
        if ($this->getRequest()->getParam('testByMollie')) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('content-type', 'text/plain');
            $result->setContents('OK', true);
            return;
        }

        if ($transactionId = $this->getRequest()->getParam('id')) {
            try {
                if ($orderId = $this->mollieModel->getOrderIdByTransactionId($transactionId)) {
                    $this->mollieModel->processTransaction($orderId, 'webhook');
                }
            } catch (\Exception $e) {
                $this->mollieHelper->addTolog('error', $e->getMessage());

                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setHttpResponseCode(503);

                return $result;
            }
        }
    }
}
