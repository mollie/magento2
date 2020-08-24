<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Mollie\Payment\Api\Webapi\PaymentTokenRequestInterface;
use Mollie\Payment\Service\PaymentToken\Generate;

class PaymentToken implements PaymentTokenRequestInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var Generate
     */
    private $paymentToken;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Generate $paymentToken
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->paymentToken = $paymentToken;
    }

    /**
     * @param CartInterface $cart
     * @return string
     */
    public function generate(CartInterface $cart)
    {
        $token = $this->paymentToken->forCart($cart);

        return $token;
    }

    /**
     * @param string $cartId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateForCustomer($cartId)
    {
        $cart = $this->cartRepository->get($cartId);

        return $this->generate($cart);
    }

    /**
     * @param string $cartId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateForGuest($cartId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cart = $this->cartRepository->get($quoteIdMask->getQuoteId());

        return $this->generate($cart);
    }
}
