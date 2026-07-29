<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

class CreateSession implements HttpPostActionInterface
{
    public const TYPE_CHECKOUT = 'checkout';
    public const TYPE_CART = 'cart';

    private const STATUS_SHIPPING_METHOD_REQUIRED = 409;

    public function __construct(
        private readonly Session $checkoutSession,
        private readonly JsonFactory $jsonFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly RequestInterface $request,
        private readonly \Mollie\Payment\Service\Mollie\CreateSession $createSession,
    ) {
    }

    public function execute()
    {
        $cart = $this->checkoutSession->getQuote();

        if ($this->isWaitingForShippingMethod($cart)) {
            return $this->shippingMethodRequiredResponse();
        }

        $this->setEmailOnCart($cart);

        $accessToken = $this->createSession->execute($cart, $this->isExpressCheckout());

        $cart->collectTotals();
        $this->cartRepository->save($cart);

        return $this->jsonFactory->create()->setData([
            'clientAccessToken' => $accessToken,
        ]);
    }

    private function getType(): string
    {
        $type = $this->request->getParam('type');

        return is_string($type) ? $type : '';
    }

    private function isExpressCheckout(): bool
    {
        return !in_array($this->getType(), [self::TYPE_CHECKOUT, self::TYPE_CART], true);
    }

    /*
     * Without a shipping method the grand total does not include the shipping costs yet, so a session created now
     * would charge the customer too little. The frontend retries once the shipping method has been selected.
     */
    private function isWaitingForShippingMethod(CartInterface $cart): bool
    {
        if ($this->getType() !== self::TYPE_CHECKOUT || $cart->getIsVirtual()) {
            return false;
        }

        return !$cart->getShippingAddress()->getShippingMethod();
    }

    private function shippingMethodRequiredResponse(): Json
    {
        return $this->jsonFactory->create()
            ->setHttpResponseCode(self::STATUS_SHIPPING_METHOD_REQUIRED)
            ->setData([
                'message' => __('A shipping method must be selected before the payment amount can be determined.')
                    ->render(),
            ]);
    }

    private function setEmailOnCart(CartInterface $cart): void
    {
        $email = $this->request->getParam('email');

        if (!$email) {
            return;
        }

        $cart->getPayment()->setAdditionalInformation('mollie_guest_email', $email);
    }
}
