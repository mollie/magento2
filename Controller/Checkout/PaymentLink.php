<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Payment\Service\Magento\PaymentLinkRedirect;

class PaymentLink implements HttpGetActionInterface
{
    public function __construct(
        private RequestInterface $request,
        private ResultFactory $resultFactory,
        private ManagerInterface $messageManager,
        private PaymentLinkRedirect $paymentLinkRedirect
    ) {}

    public function execute()
    {
        $orderKey = $this->request->getParam('order');
        if (!$orderKey) {
            return $this->returnStatusCode(400);
        }

        try {
            $result = $this->paymentLinkRedirect->execute($orderKey);
        } catch (NoSuchEntityException $exception) {
            return $this->returnStatusCode(404);
        }

        if ($result->isExpired()) {
            $this->messageManager->addErrorMessage(__('Your payment link has expired.'));

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl('/');
        }

        if ($result->isAlreadyPaid()) {
            $this->messageManager->addSuccessMessage(__('Your order has already been paid.'));

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl('/');
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($result->getRedirectUrl());
    }

    public function returnStatusCode(int $code): ResultInterface
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setHttpResponseCode($code);
    }
}
