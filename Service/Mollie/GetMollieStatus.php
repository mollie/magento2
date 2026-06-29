<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\ResolvePaymentId;

class GetMollieStatus
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private MollieApiClient $mollieApiClient,
        private GetMollieStatusResultFactory $getMollieStatusResultFactory,
        private ResolvePaymentId $resolvePaymentId
    ) {}

    public function execute(int $orderId, ?string $transactionId = null): GetMollieStatusResult
    {
        $order = $this->orderRepository->get($orderId);
        if ($transactionId === null) {
            $transactionId = $order->getMollieTransactionId();
        }

        if ($transactionId === null) {
            throw new LocalizedException(__('No transaction ID found for order %1', $orderId));
        }

        $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));
        $paymentId = $this->resolvePaymentId->execute($mollieApi, $transactionId);
        $molliePayment = $mollieApi->payments->get($paymentId);

        return $this->getMollieStatusResultFactory->create([
            'status' => $molliePayment->status,
            'method' => $molliePayment->method,
        ]);
    }
}
