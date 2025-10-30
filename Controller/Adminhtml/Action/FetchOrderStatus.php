<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Mollie;

class FetchOrderStatus extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private Mollie $mollieModel,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $orderId = $this->getRequest()->getParam('order_id');

            $orderHasUpdate = $this->mollieModel->orderHasUpdate($orderId);
            if (!$orderHasUpdate) {
                $this->messageManager->addSuccessMessage(__('The latest status from Mollie has been retrieved'));

                return $result->setData([]);
            }

            $message = $this->mollieModel->processTransaction($orderId, 'webhook');
            $this->throwExceptionIfErrorPresent($message);

            $this->messageManager->addSuccessMessage(__('The latest status from Mollie has been retrieved'));

            return $result->setData($message->toArray());
        } catch (Exception $exception) {
            $result->setHttpResponseCode(503);

            return $result->setData([
                'error' => true,
                'msg' => $exception->getMessage(),
            ]);
        }
    }

    private function throwExceptionIfErrorPresent(ProcessTransactionResponse $message): void
    {
        if ($message->isSuccess()) {
            return;
        }

        throw new LocalizedException(__($message->getStatus()));
    }
}
