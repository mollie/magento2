<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Api\Webapi\StartTransactionRequestInterface;
use Mollie\Payment\Service\Order\Transaction;

class StartTransaction implements StartTransactionRequestInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private Transaction $transaction,
        private \Mollie\Payment\Service\Mollie\StartTransaction $startTransaction
    ) {}

    /**
     * @param string $token
     * @return string
     * @throws LocalizedException
     * @throws ApiException
     */
    public function execute(string $token): string
    {
        $model = $this->paymentTokenRepository->getByToken($token);
        $order = $this->orderRepository->get($model->getOrderId());

        $checkoutUrl = $this->startTransaction->execute($order);
        if ($checkoutUrl !== null) {
            return $checkoutUrl;
        }

        // If the order is paid with a payment method without hosted payment page,
        // we need to redirect to the success page. As the order is instantly paid.
        return $this->transaction->getRedirectUrl(
            $order,
            $token,
        );
    }
}
