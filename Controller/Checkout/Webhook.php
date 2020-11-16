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
            return $this->getOkResponse();
        }

        if ($transactionId = $this->getRequest()->getParam('id')) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            try {
                if ($orderId = $this->mollieModel->getOrderIdByTransactionId($transactionId)) {
                    $this->mollieModel->processTransaction($orderId, 'webhook');

                    return $this->getOkResponse();
                }

                $result->setData([
                    'error' => true,
                    'message' => __('There is no order found that belongs to "%1"', $transactionId)->render(),
                ]);
                $result->setHttpResponseCode(404);

                return $result;
            } catch (\Exception $e) {
                $this->mollieHelper->addTolog('error', $e->getMessage());

                $result->setData(['error' => true]);
                $result->setHttpResponseCode(503);

                return $result;
            }
        }
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     */
    private function getOkResponse()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('content-type', 'text/plain');
        $result->setContents('OK', true);
        return $result;
    }
}
