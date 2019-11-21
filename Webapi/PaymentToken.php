<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterfaceFactory;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Api\Webapi\PaymentTokenRequestInterface;
use Mollie\Payment\Helper\General as MollieHelper;

class PaymentToken implements PaymentTokenRequestInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var MollieHelper
     */
    private $helper;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

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
        MollieHelper $helper,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenInterfaceFactory $paymentTokenFactory
    ) {
        $this->helper = $helper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->cartRepository = $cartRepository;
    }

    public function generate(CartInterface $cart): string
    {
        $token = $this->getUniquePaymentToken();

        /** @var PaymentTokenInterface $model */
        $model = $this->paymentTokenFactory->create();
        $model->setCartId($cart->getId());
        $model->setToken($token);

        $this->paymentTokenRepository->save($model);

        return $token;
    }

    public function generateForCustomer($cartId): string
    {
        $cart = $this->cartRepository->get($cartId);

        return $this->generate($cart);
    }

    public function generateForGuest($cartId): string
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cart = $this->cartRepository->get($quoteIdMask->getQuoteId());

        return $this->generate($cart);
    }

    /**
     * @return string
     */
    private function getUniquePaymentToken(): string
    {
        $token = $this->helper->getPaymentToken();

        /**
         * If the token already exists, call this function again to generate a new token.
         */
        if ($this->paymentTokenRepository->getByToken($token)) {
            return $this->getUniquePaymentToken();
        }

        return $token;
    }
}
