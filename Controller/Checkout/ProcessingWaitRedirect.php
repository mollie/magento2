<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetMollieStatusResultFactory;
use Mollie\Payment\Service\Mollie\Order\AddResultMessage;
use Mollie\Payment\Service\Mollie\Order\SuccessPageRedirect;
use Mollie\Payment\Service\Order\RedirectOnError;

class ProcessingWaitRedirect implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EncryptorInterface $encryptor,
        private readonly Mollie $mollieModel,
        private readonly GetMollieStatusResultFactory $getMollieStatusResultFactory,
        private readonly SuccessPageRedirect $successPageRedirect,
        private readonly Session $checkoutSession,
        private readonly AddResultMessage $addResultMessage,
        private readonly RedirectOnError $redirectOnError,
    ) {}

    public function execute(): ResponseInterface
    {
        $token = $this->request->getParam('token');
        if (!$token) {
            throw new AuthorizationException(__('Invalid token'));
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $orderId = (int) $this->encryptor->decrypt(base64_decode($token));
        $order = $this->orderRepository->get($orderId);

        $processResult = $this->mollieModel->processTransactionForOrder($order, 'success');

        $result = $this->getMollieStatusResultFactory->create([
            'status' => $processResult->getStatus(),
            'method' => $order->getPayment()->getAdditionalInformation('method') ?? $order->getPayment()->getMethod(),
        ]);

        if ($result->shouldRedirectToSuccessPage()) {
            return $this->successPageRedirect->redirectToSuccessPage($order, [(int) $order->getEntityId() => null]);
        }

        $this->checkoutSession->restoreQuote();
        $this->addResultMessage->execute($result);

        $this->response->setRedirect($this->redirectOnError->getUrl());

        return $this->response;
    }
}
