<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Mollie\Payment\Api\Webapi\PaymentTokenRequestInterface;
use Mollie\Payment\Service\PaymentToken\Generate;

class PaymentToken implements PaymentTokenRequestInterface
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private GuestCartRepositoryInterface $guestCartRepository,
        private Generate $paymentToken
    ) {}

    /**
     * @param CartInterface $cart
     * @return string
     */
    public function generate(CartInterface $cart): string
    {
        $token = $this->paymentToken->forCart($cart);

        return $token;
    }

    /**
     * @param string $cartId
     * @return string
     * @throws NoSuchEntityException
     */
    public function generateForCustomer(string $cartId): string
    {
        $cart = $this->cartRepository->get($cartId);

        return $this->generate($cart);
    }

    /**
     * @param string $cartId
     * @return string
     * @throws NoSuchEntityException
     */
    public function generateForGuest(string $cartId): string
    {
        $cart = $this->guestCartRepository->get($cartId);

        return $this->generate($cart);
    }
}
