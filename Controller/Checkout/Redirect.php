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
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\FormatExceptionMessages;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;

class Redirect extends Action implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        protected Session $checkoutSession,
        protected Data $paymentHelper,
        private OrderManagementInterface $orderManagement,
        private Config $config,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private OrderRepositoryInterface $orderRepository,
        private RedirectUrl $redirectUrl,
        private FormatExceptionMessages $formatExceptionMessages,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Mollie after placing order
     */
    public function execute(): ResponseInterface
    {
        try {
            $order = $this->getOrder();
        } catch (LocalizedException $exception) {
            $this->config->addTolog('error', $exception->getMessage());

            return $this->_redirect('checkout/cart');
        }

        try {
            if ($order->getPayment() === null) {
                return $this->_redirect('checkout/cart');
            }

            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->getMethodInstance($method);
            if (!$methodInstance instanceof Mollie) {
                $msg = __('Payment Method not found');
                $this->messageManager->addErrorMessage($msg);
                $this->config->addTolog('error', $msg);
                $this->checkoutSession->restoreQuote();

                return $this->_redirect('checkout/cart');
            }

            return $this->getResponse()->setRedirect(
                $this->redirectUrl->execute($methodInstance, $order),
            );
        } catch (Exception $exception) {
            $errorMessage = $this->formatExceptionMessages->execute($exception, $methodInstance ?? null);
            $this->messageManager->addErrorMessage($errorMessage);
            $this->config->addTolog('error', $exception->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->cancelUnprocessedOrder($order, $exception->getMessage());

            return $this->_redirect('checkout/cart');
        }
    }

    private function cancelUnprocessedOrder(OrderInterface $order, string $message): void
    {
        if (!$this->config->cancelFailedOrders()) {
            return;
        }

        try {
            $historyMessage = __('Canceled because an error occurred while redirecting the customer to Mollie');
            if ($message) {
                $historyMessage .= ':<br>' . PHP_EOL . $message;
            }

            $order->setState(Order::STATE_PENDING_PAYMENT);
            $this->orderManagement->cancel($order->getEntityId());
            $order->addCommentToStatusHistory($order->getEntityId(), $historyMessage);

            $this->config->addToLog('info', sprintf('Canceled order %s', $order->getIncrementId()));
        } catch (Exception $e) {
            $message = sprintf('Cannot cancel order %s: %s', $order->getIncrementId(), $e->getMessage());
            $this->config->addToLog('error', $message);
        }
    }

    private function getOrder(): OrderInterface
    {
        $token = $this->getRequest()->getParam('paymentToken');
        if (!$token) {
            throw new LocalizedException(__('The required payment token is not available'));
        }

        $model = $this->paymentTokenRepository->getByToken($token);
        if (!$model) {
            throw new LocalizedException(__('The payment token %1 does not exists', $token));
        }

        return $this->orderRepository->get($model->getOrderId());
    }

    private function getMethodInstance(string $method): MethodInterface
    {
        $methodInstance = $this->paymentHelper->getMethodInstance($method);

        if ($methodInstance instanceof CreditcardVault) {
            return $this->paymentHelper->getMethodInstance('mollie_methods_creditcard');
        }

        return $methodInstance;
    }
}
