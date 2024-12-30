<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\Order\AddResultMessage;
use Mollie\Payment\Service\Mollie\Order\SuccessPageRedirect;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Service\Mollie\ValidateProcessRequest;
use Mollie\Payment\Service\Order\RedirectOnError;

/**
 * Class Process
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Process extends Action
{

    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    /**
     * @var MollieModel
     */
    protected $mollieModel;
    /**
     * @var MollieHelper
     */
    protected $mollieHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectOnError
     */
    private $redirectOnError;
    /**
     * @var ValidateProcessRequest
     */
    private $validateProcessRequest;
    /**
     * @var ProcessTransaction
     */
    private $processTransaction;
    /**
     * @var SuccessPageRedirect
     */
    private $successPageRedirect;
    /**
     * @var AddResultMessage
     */
    private $addResultMessage;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        RedirectOnError $redirectOnError,
        ValidateProcessRequest $validateProcessRequest,
        ProcessTransaction $processTransaction,
        SuccessPageRedirect $successPageRedirect,
        AddResultMessage $addResultMessage
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->redirectOnError = $redirectOnError;
        $this->validateProcessRequest = $validateProcessRequest;
        $this->processTransaction = $processTransaction;
        $this->successPageRedirect = $successPageRedirect;
        $this->addResultMessage = $addResultMessage;

        parent::__construct($context);
    }

    /**
     * Return from mollie after payment
     */
    public function execute()
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
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));
            return $this->_redirect($this->redirectOnError->getUrl());
        }

        if ($result !== null && $result->shouldRedirectToSuccessPage()) {
            try {
                $this->successPageRedirect->execute($order, $orderIds);
                return $this->getResponse();
            } catch (\Exception $e) {
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
    protected function checkIfLastRealOrder(array $orderIds)
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
