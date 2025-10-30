<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

class GetMollieStatus
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private MollieApiClient $mollieApiClient,
        private GetMollieStatusResultFactory $getMollieStatusResultFactory
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

        if (substr($transactionId, 0, 4) == 'ord_') {
            $mollieOrder = $mollieApi->orders->get($transactionId);

            return $this->getMollieStatusResultFactory->create([
                'status' => $mollieOrder->status,
                'method' => $mollieOrder->method,
            ]);
        }

        $molliePayment = $mollieApi->payments->get($transactionId);

        return $this->getMollieStatusResultFactory->create([
            'status' => $molliePayment->status,
            'method' => $molliePayment->method,
        ]);
    }
}
