<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Express;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultInterfaceFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Config;

class RedirectToCart implements HttpGetActionInterface
{
    public function __construct(
        readonly private RequestInterface $request,
        readonly private ManagerInterface $messageManager,
        readonly private CartRepositoryInterface $cartRepository,
        readonly private ResultFactory $resultFactory,
        readonly private Config $config,
        readonly private PaymentTokenRepositoryInterface $paymentTokenRepository,
    ) {}

    public function execute(): ResultInterface
    {
        $token = $this->request->getParam('token');
        $paymentToken = $this->paymentTokenRepository->getByToken($token);
        $cartId = $paymentToken->getCartId();
        $cart = $this->cartRepository->get($cartId);

        $this->config->addToLog('error', [
            'message' => 'Order creation timed out for quote, redirecting user to quote',
            'quoteId' => $cart->getId(),
            'orderId' => $paymentToken->getOrderId(),
        ]);

        $this->messageManager->addErrorMessage(
            __('Something went wrong while processing your order. Please try again later.')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('checkout/cart');
    }
}
