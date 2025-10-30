<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\PaymentToken\Generate;

class Pointofsale implements HttpGetActionInterface
{
    public function __construct(
        private PageFactory $resultPageFactory,
        private RequestInterface $request,
        private StoreManagerInterface $storeManager,
        private UrlInterface $url,
        private OrderRepositoryInterface $orderRepository,
        private EncryptorInterface $encryptor,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private Generate $generatePaymentToken
    ) {}

    public function execute()
    {
        $token = $this->request->getParam('token');
        if (!$token) {
            throw new AuthorizationException(__('Invalid token'));
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $id = $this->encryptor->decrypt(base64_decode($token));
        $order = $this->orderRepository->get($id);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Please finish the payment on the terminal.'));

        /** @var Template $block */
        $block = $resultPage->getLayout()->getBlock('mollie.pointofsale.wait');
        $block->setData(
            'status_url',
            '/rest/' . $this->storeManager->getStore()->getCode() . '/V1/mollie/get-order/' . $token,
        );
        $block->setData(
            'reset_url',
            '/rest/' . $this->storeManager->getStore()->getCode() . '/V1/mollie/reset-cart/' . $token,
        );
        $block->setData(
            'retry_url',
            $this->url->getUrl('mollie/checkout/redirect', ['paymentToken' => $this->getToken($order)]),
        );

        return $resultPage;
    }

    private function getToken(OrderInterface $order): string
    {
        $token = $this->paymentTokenRepository->getByOrder($order);
        if ($token) {
            return $token->getToken();
        }

        return $this->generatePaymentToken->forOrder($order)->getToken();
    }
}
