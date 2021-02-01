<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Order\Reorder;
use Mollie\Payment\Service\PaymentToken\Generate;

class SecondChance extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var Reorder
     */
    private $reorder;

    /**
     * @var Paymentlink
     */
    private $paymentlink;

    /**
     * @var Generate
     */
    private $generatePaymentToken;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Reorder $reorder,
        Paymentlink $paymentlink,
        Generate $paymentTokenPaymentToken
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->reorder = $reorder;
        $this->paymentlink = $paymentlink;
        $this->generatePaymentToken = $paymentTokenPaymentToken;
    }

    public function execute()
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
     * @return \Magento\Framework\App\ResponseInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    private function reorder()
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return PaymentTokenInterface|null
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
