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
use Mollie\Payment\Service\Mollie\MollieApiClient;

class Process implements HttpGetActionInterface
{
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
    ) {}

    public function execute(): ResultInterface
    {
        $token = $this->request->getParam('paymentToken');
        $cartId = $this->paymentTokenRepository->getByToken($token)->getCartId();
        $cart = $this->cartRepository->get($cartId);

        $sessionId = $cart->getPayment()->getAdditionalInformation('mollie_session_id');
        $mollie = $this->mollieApiClient->loadByStore($cart->getStoreId());
        $session = $mollie->sessions->get($sessionId);

        /** @var 'open'|'expired'|'completed' $status */
        $status = $session->status;

        if ($status != 'completed') {
            return $this->redirectToCart($cart);
        }

        $cart->setIsActive(0);
        $this->cartRepository->save($cart);

        return $this->renderResultPage($token);
    }

    private function redirectToCart(CartInterface $cart): ResultInterface
    {
        $cart->setIsActive(true);
        $this->cartRepository->save($cart);

        $this->messageManager->addErrorMessage(
            __('Your payment was not completed. Please try again or select another payment method.')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('checkout/cart');
    }

    private function renderResultPage(string $token): Page
    {
        $resultPage = $this->resultPageFactory->create();

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
