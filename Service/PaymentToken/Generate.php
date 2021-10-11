<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentToken;

use Magento\Framework\Math\Random;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterfaceFactory;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;

class Generate
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        Random $mathRandom,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenInterfaceFactory $paymentTokenFactory
    ) {
        $this->cartRepository = $cartRepository;
        $this->mathRandom = $mathRandom;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    /**
     * @param CartInterface $cart
     * @return string
     */
    public function forCart(CartInterface $cart)
    {
        $token = $this->getUniquePaymentToken();

        /** @var PaymentTokenInterface $model */
        $model = $this->paymentTokenFactory->create();
        $model->setCartId($cart->getId());
        $model->setToken($token);

        $this->paymentTokenRepository->save($model);

        return $token;
    }

    /**
     * @param OrderInterface $order
     * @return PaymentTokenInterface
     */
    public function forOrder(OrderInterface $order): PaymentTokenInterface
    {
        $token = $this->getUniquePaymentToken();

        /** @var PaymentTokenInterface $model */
        $model = $this->paymentTokenFactory->create();
        $model->setCartId($order->getQuoteId());
        $model->setOrderId($order->getId());
        $model->setToken($token);

        $this->paymentTokenRepository->save($model);

        return $model;
    }

    /**
     * @return string
     */
    private function getUniquePaymentToken()
    {
        $token = $this->mathRandom->getUniqueHash();

        /**
         * If the token already exists, call this function again to generate a new token.
         */
        if ($this->paymentTokenRepository->getByToken($token)) {
            return $this->getUniquePaymentToken();
        }

        return $token;
    }
}
