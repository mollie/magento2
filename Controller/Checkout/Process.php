<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\Order\AddResultMessage;
use Mollie\Payment\Service\Mollie\Order\SuccessPageRedirect;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Service\Mollie\ValidateProcessRequest;
use Mollie\Payment\Service\Order\RedirectOnError;

class Process extends Action implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        protected Session $checkoutSession,
        protected Data $paymentHelper,
        protected Mollie $mollieModel,
        protected General $mollieHelper,
        private OrderRepositoryInterface $orderRepository,
        private RedirectOnError $redirectOnError,
        private ValidateProcessRequest $validateProcessRequest,
        private ProcessTransaction $processTransaction,
        private SuccessPageRedirect $successPageRedirect,
        private AddResultMessage $addResultMessage,
    ) {
        parent::__construct($context);
    }

    /**
     * Return from mollie after payment
     */
    public function execute(): ResponseInterface
    {
        $orderIds = $this->validateProcessRequest->execute();
        if (!$orderIds) {
            $this->mollieHelper->addTolog('error', __('Invalid return, missing order id.'));
            $this->messageManager->addNoticeMessage(__('Invalid return from Mollie.'));

            return $this->_redirect($this->redirectOnError->getUrl());
        }

        try {
            $result = null;
            foreach ($orderIds as $orderId => $paymentToken) {
                $order = $this->orderRepository->get($orderId);
                $result = $this->processTransaction->execute($orderId, $order->getMollieTransactionId(), 'success');
            }
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));

            return $this->_redirect($this->redirectOnError->getUrl());
        }

        if ($result !== null && $result->shouldRedirectToSuccessPage()) {
            try {
                $this->successPageRedirect->execute($order, $orderIds);

                return $this->getResponse();
            } catch (Exception $e) {
                $this->mollieHelper->addTolog('error', $e->getMessage());
                $this->messageManager->addErrorMessage(__('Transaction failed. Please verify your billing information and payment method, and try again.'));

                return $this->_redirect($this->redirectOnError->getUrl());
            }
        }

        return $this->handleNonSuccessResult($result, $orderIds);
    }

    protected function handleNonSuccessResult(GetMollieStatusResult $result, array $orderIds): ResponseInterface
    {
        $this->checkIfLastRealOrder($orderIds);
        $this->checkoutSession->restoreQuote();
        $this->addResultMessage->execute($result);

        return $this->_redirect($this->redirectOnError->getUrl());
    }

    /**
     * @param array $orderIds
     */
    protected function checkIfLastRealOrder(array $orderIds): void
    {
        if ($this->checkoutSession->getLastRealOrder()->getId()) {
            return;
        }

        try {
            $order = $this->orderRepository->get(array_key_last($orderIds));
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        } catch (NoSuchEntityException $exception) {
            //
        }
    }
}
