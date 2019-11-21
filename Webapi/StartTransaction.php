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

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * @param $token
     * @return string
     * @throws LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function execute($token)
    {
        $model = $this->paymentTokenRepository->getByToken($token);
        $order = $this->orderRepository->get($model->getOrderId());

        /** @var \Mollie\Payment\Model\Mollie $instance */
        $instance = $order->getPayment()->getMethodInstance();

        return $instance->startTransaction($order);
    }
}
