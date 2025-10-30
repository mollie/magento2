<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private Random $mathRandom,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private PaymentTokenInterfaceFactory $paymentTokenFactory
    ) {}

    public function forCart(CartInterface $cart): string
    {
        $token = $this->getUniquePaymentToken();

        /** @var PaymentTokenInterface $model */
        $model = $this->paymentTokenFactory->create();
        $model->setCartId($cart->getId());
        $model->setToken($token);

        $this->paymentTokenRepository->save($model);

        return $token;
    }

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

    private function getUniquePaymentToken(): string
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
