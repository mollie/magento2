<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Exceptions\CustomerDetailsRequired;
use Mollie\Payment\Service\Mollie\Express\ExpressComponentsBeta;
use Mollie\Payment\Service\Mollie\Express\ProductBuyRequest;
use Mollie\Payment\Service\Mollie\Express\ProductQuote;
use Mollie\Payment\Service\Mollie\Express\ProductSessions;
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
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_PRODUCT = 'product';

    private const STATUS_SHIPPING_METHOD_REQUIRED = 409;
    private const STATUS_PRODUCT_NOT_AVAILABLE = 422;
    private const STATUS_NOT_FOUND = 404;
    private const STATUS_CUSTOMER_DETAILS_REQUIRED = 422;

    public function __construct(
        private readonly Session $checkoutSession,
        private readonly JsonFactory $jsonFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly RequestInterface $request,
        private readonly \Mollie\Payment\Service\Mollie\CreateSession $createSession,
        private readonly ProductQuote $productQuote,
        private readonly ProductSessions $productSessions,
        private readonly CustomerSession $customerSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly ExpressComponentsBeta $beta,
    ) {
    }

    public function execute()
    {
        if ($this->getType() === self::TYPE_PRODUCT) {
            return $this->beta->isEnabled() ? $this->createProductSession() : $this->notFoundResponse();
        }

        $cart = $this->checkoutSession->getQuote();

        if ($this->isWaitingForShippingMethod($cart)) {
            return $this->shippingMethodRequiredResponse();
        }

        $this->setEmailOnCart($cart);

        try {
            $accessToken = $this->createSession->execute($cart, $this->isExpressCheckout());
        } catch (CustomerDetailsRequired $exception) {
            return $this->customerDetailsRequiredResponse($exception);
        }

        $cart->collectTotals();
        $this->cartRepository->save($cart);

        return $this->jsonFactory->create()->setData([
            'clientAccessToken' => $accessToken,
        ]);
    }

    /*
     * The product page quote is built in memory and never saved, so a visitor does not get a quote for every
     * product page they open.
     */
    private function createProductSession(): Json
    {
        try {
            $buyRequest = ProductBuyRequest::fromRequestParams($this->request->getParams());
            $cart = $this->productQuote->create(
                $buyRequest,
                (int)$this->storeManager->getStore()->getId(),
                $this->getCustomerId(),
            );
        } catch (LocalizedException $exception) {
            return $this->productNotAvailableResponse($exception);
        }

        $session = $this->createSession->executeForProduct($cart, $buyRequest);
        $this->productSessions->remember($session->paymentToken, $session->sessionId);

        return $this->jsonFactory->create()->setData([
            'clientAccessToken' => $session->clientAccessToken,
        ]);
    }

    private function getCustomerId(): ?int
    {
        return $this->customerSession->isLoggedIn() ? (int)$this->customerSession->getCustomerId() : null;
    }

    /*
     * A configurable or a bundle product cannot be bought before every required option is selected. The frontend
     * then keeps the buttons hidden and tries again after the next change.
     */
    private function productNotAvailableResponse(LocalizedException $exception): Json
    {
        return $this->jsonFactory->create()
            ->setHttpResponseCode(self::STATUS_PRODUCT_NOT_AVAILABLE)
            ->setData([
                'message' => $exception->getMessage(),
            ]);
    }

    private function getType(): string
    {
        $type = $this->request->getParam('type');

        return is_string($type) ? $type : '';
    }

    /*
     * Only the payment step has a selected shipping method. With the beta Mollie collects the address and offers
     * the shipping options everywhere else, so the session must not contain the shipping costs yet. Without it the
     * cart page charges the grand total, including the shipping costs estimated on the cart page.
     */
    private function isExpressCheckout(): bool
    {
        if ($this->beta->isEnabled()) {
            return $this->getType() !== self::TYPE_CHECKOUT;
        }

        return !in_array($this->getType(), [self::TYPE_CHECKOUT, self::TYPE_CART], true);
    }

    /*
     * Without the beta Mollie cannot collect missing customer details. The frontend keeps the buttons hidden, and
     * nothing is logged, as this happens for every guest that opens the minicart.
     */
    private function customerDetailsRequiredResponse(CustomerDetailsRequired $exception): Json
    {
        return $this->jsonFactory->create()
            ->setHttpResponseCode(self::STATUS_CUSTOMER_DETAILS_REQUIRED)
            ->setData([
                'message' => $exception->getMessage(),
            ]);
    }

    private function notFoundResponse(): Json
    {
        return $this->jsonFactory->create()->setHttpResponseCode(self::STATUS_NOT_FOUND)->setData([]);
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
