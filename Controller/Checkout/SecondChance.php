<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Order\Reorder;
use Mollie\Payment\Service\PaymentToken\Generate;

class SecondChance extends Action implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        private OrderRepositoryInterface $orderRepository,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private Reorder $reorder,
        private Generate $generatePaymentToken,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResponseInterface
    {
        try {
            return $this->reorder();
        } catch (NoSuchEntityException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->messageManager->addExceptionMessage($exception);

            return $this->_redirect('/');
        }
    }

    /**
     * @return ResponseInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws ApiException
     */
    private function reorder(): ResponseInterface
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $paymentToken = $this->getRequest()->getParam('payment_token');
        $order = $this->orderRepository->get($orderId);
        $token = $this->paymentTokenRepository->getByToken($paymentToken);

        if (!$token || $order->getEntityId() != $token->getOrderId()) {
            throw new NoSuchEntityException();
        }

        $information = $order->getPayment()->getAdditionalInformation();
        $state = $order->getState();
        if (in_array($state, [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT]) && isset($information['checkout_url'])) {
            return $this->_redirect($information['checkout_url']);
        }

        $order = $this->reorder->create($order);

        $token = $this->getToken($order);
        $url = $this->_url->getUrl('mollie/checkout/redirect', ['paymentToken' => $token->getToken()]);

        return $this->_redirect($url);
    }

    /**
     * @param OrderInterface $order
     * @throws LocalizedException
     * @return PaymentTokenInterface
     */
    private function getToken(OrderInterface $order): PaymentTokenInterface
    {
        $token = $this->paymentTokenRepository->getByOrder($order);
        if ($token) {
            return $token;
        }

        return $this->generatePaymentToken->forOrder($order);
    }
}
