<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Express;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\SuccessPageRedirect;

class Redirect implements HttpGetActionInterface
{
    public function __construct(
        readonly private RequestInterface $request,
        readonly private OrderRepositoryInterface $orderRepository,
        readonly private PaymentTokenRepositoryInterface $paymentTokenRepository,
        readonly private SuccessPageRedirect $successPageRedirect,
    ) {}


    public function execute(): ResponseInterface
    {
        $token = $this->request->getParam('token');
        $paymentToken = $this->paymentTokenRepository->getByToken($token);
        if ($paymentToken->getOrderId() === null) {
            throw new NotFoundException(__('The payment token exists but does not have a order connected.'));
        }

        $order = $this->orderRepository->get($paymentToken->getOrderId());

        return $this->successPageRedirect->execute($order, [$paymentToken->getOrderId()]);
    }
}
