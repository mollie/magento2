<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Mollie\Express\ProductSessions;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class Process implements HttpGetActionInterface
{
    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        readonly private RequestInterface $request,
        readonly private CartRepositoryInterface $cartRepository,
        readonly private PaymentTokenRepositoryInterface $paymentTokenRepository,
        readonly private PageFactory $resultPageFactory,
        readonly private StoreManagerInterface $storeManager,
        readonly private MollieApiClient $mollieApiClient,
        readonly private ResultFactory $resultFactory,
        readonly private ManagerInterface $messageManager,
        readonly private UrlInterface $urlBuilder,
        readonly private ProductSessions $productSessions,
    ) {}

    public function execute(): ResultInterface
    {
        $token = (string)$this->request->getParam('paymentToken');

        $productSessionId = $this->productSessions->find($token);
        if ($productSessionId !== null) {
            return $this->processProductSession($token, $productSessionId);
        }

        // Banking apps often open the redirect in another browser, which does not know the product page session.
        // Its token only exists once the webhook placed the order, and then belongs to the cart of that order,
        // which has no session id. The wait page waits for the order, and sends the customer to the cart when it
        // does not show up.
        $paymentToken = $this->paymentTokenRepository->getByToken($token);
        if ($paymentToken === null) {
            return $this->renderResultPage($token);
        }

        $cart = $this->cartRepository->get((int)$paymentToken->getCartId());

        $sessionId = (string)$cart->getPayment()->getAdditionalInformation('mollie_session_id');
        if ($sessionId === '') {
            return $this->renderResultPage($token);
        }

        $mollie = $this->mollieApiClient->loadByStore($cart->getStoreId());
        $session = $mollie->sessions->get($sessionId);

        /** @var 'open'|'expired'|'completed' $status */
        $status = $session->status;

        if ($status != self::STATUS_COMPLETED) {
            return $this->redirectToCart($cart);
        }

        $cart->setIsActive(false);
        $this->cartRepository->save($cart);

        return $this->renderResultPage($token);
    }

    /*
     * A product page session has no cart, and the webhook may not have created the order and its payment token yet.
     * The shopping cart of the customer was never touched, so it stays as it is.
     */
    private function processProductSession(string $token, string $sessionId): ResultInterface
    {
        $mollie = $this->mollieApiClient->loadByStore($this->getStoreId());

        if ($mollie->sessions->get($sessionId)->status !== self::STATUS_COMPLETED) {
            return $this->paymentNotCompletedResponse();
        }

        return $this->renderResultPage($token);
    }

    /**
     * Store views that share a domain without store codes in their URLs all resolve to the same store, so the
     * redirect URL carries the store id of the session.
     */
    private function getStoreId(): int
    {
        $storeId = (int)$this->request->getParam('storeId');
        if ($storeId !== 0) {
            return $storeId;
        }

        return (int)$this->storeManager->getStore()->getId();
    }

    private function redirectToCart(CartInterface $cart): ResultInterface
    {
        $cart->setIsActive(true);
        $this->cartRepository->save($cart);

        return $this->paymentNotCompletedResponse();
    }

    private function paymentNotCompletedResponse(): ResultInterface
    {
        $this->messageManager->addErrorMessage(
            __('Your payment was not completed. Please try again or select another payment method.')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('checkout/cart');
    }

    private function renderResultPage(string $token): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Confirming your payment')->render());

        /** @var Template $block */
        $block = $resultPage->getLayout()->getBlock('mollie.express.wait');

        $block->setData(
            'status_url',
            '/rest/' . $this->storeManager->getStore()->getCode() . '/V1/mollie/get-order/by-payment-token/' . $token,
        );

        $block->setData(
            'redirect_url',
            $this->urlBuilder->getUrl('mollie/express/redirect', ['token' => $token])
        );

        $block->setData(
            'redirect_to_cart_url',
            $this->urlBuilder->getUrl('mollie/express/redirectToCart', ['token' => $token])
        );

        return $resultPage;
    }
}
