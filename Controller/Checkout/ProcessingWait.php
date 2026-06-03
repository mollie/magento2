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
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProcessingWait implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $resultPageFactory,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EncryptorInterface $encryptor,
    ) {}

    public function execute()
    {
        $token = $this->request->getParam('token');
        if (!$token) {
            throw new AuthorizationException(__('Invalid token'));
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $this->orderRepository->get((int) $this->encryptor->decrypt(base64_decode($token)));

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Confirming your payment'));

        /** @var Template $block */
        $block = $resultPage->getLayout()->getBlock('mollie.processing.wait');
        $block->setData(
            'status_url',
            '/rest/' . $this->storeManager->getStore()->getCode() . '/V1/mollie/get-order/by-hash/' . $token,
        );
        $block->setData(
            'redirect_url',
            $this->url->getUrl('mollie/checkout/processingwaitredirect', ['token' => $token]),
        );
        $block->setData(
            'reset_url',
            '/rest/' . $this->storeManager->getStore()->getCode() . '/V1/mollie/reset-cart/' . $token,
        );
        $block->setData(
            'cart_url',
            $this->url->getUrl('checkout/cart'),
        );

        return $resultPage;
    }
}
