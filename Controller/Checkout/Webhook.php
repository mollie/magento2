<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\Controller\Result\Json;
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

        $transactionId = $this->getRequest()->getParam('id');
        if (!$transactionId) {
            return $this->getErrorResponse(404, __('No transaction ID found')->render());
        }

        try {
            $orderIds = $this->mollieModel->getOrderIdsByTransactionId($transactionId);
            if (!$orderIds) {
                return $this->getErrorResponse(
                    404,
                    __('There is no order found that belongs to "%1"', $transactionId)->render()
                );
            }

            foreach ($orderIds as $orderId) {
                $this->mollieModel->processTransaction($orderId, 'webhook');
            }

            return $this->getOkResponse();
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());

            return $this->getErrorResponse(503);
        }
    }

    private function getOkResponse()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('content-type', 'text/plain');
        $result->setContents('OK');
        return $result;
    }

    private function getErrorResponse(int $code, string $message = null): Json
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(['error' => true]);

        if ($message) {
            $result->setData(['message' => $message]);
        }

        $result->setHttpResponseCode($code);

        return $result;
    }
}
