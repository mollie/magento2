<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterfaceFactory;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;

/**
 * The redirect URL of a product page session contains a token before there is a cart to store it for. Once the
 * webhook placed the order, the token gets its row so the wait page can find the order.
 */
class ProductPaymentToken
{
    public function __construct(
        private readonly PaymentTokenRepositoryInterface $paymentTokenRepository,
        private readonly PaymentTokenInterfaceFactory $paymentTokenFactory,
    ) {}

    public function link(OrderInterface $order, string $token): void
    {
        if ($token === '' || $this->paymentTokenRepository->getByToken($token) !== null) {
            return;
        }

        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setCartId((int)$order->getQuoteId());
        $paymentToken->setOrderId((int)$order->getEntityId());
        $paymentToken->setToken($token);

        $this->paymentTokenRepository->save($paymentToken);
    }
}
