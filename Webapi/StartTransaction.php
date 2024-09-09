<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Api\Webapi\StartTransactionRequestInterface;
use Mollie\Payment\Service\Order\Transaction;

class StartTransaction implements StartTransactionRequestInterface
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
     * @var Transaction
     */
    private $transaction;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Transaction $transaction
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->transaction = $transaction;
    }

    /**
     * @param string $token
     * @return string
     * @throws LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function execute(string $token)
    {
        $model = $this->paymentTokenRepository->getByToken($token);
        $order = $this->orderRepository->get($model->getOrderId());

        /** @var \Mollie\Payment\Model\Mollie $instance */
        $instance = $order->getPayment()->getMethodInstance();

        $checkoutUrl = $instance->startTransaction($order);
        if ($checkoutUrl !== null) {
            return $checkoutUrl;
        }

        // If the order is paid with a payment method without hosted payment page,
        // we need to redirect to the success page. As the order is instantly paid.
        return $this->transaction->getRedirectUrl(
            $order,
            $token
        );
    }
}
