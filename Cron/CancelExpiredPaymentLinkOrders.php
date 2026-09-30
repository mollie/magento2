<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Cron;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Service\Order\ExpiredPaymentLinkOrders;
use Throwable;

class CancelExpiredPaymentLinkOrders
{
    private const CANCEL_REASON = 'expired';

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ExpiredPaymentLinkOrders $expiredPaymentLinkOrders,
        private readonly ProcessTransaction $processTransaction,
        private readonly CancelOrder $cancelOrder,
        private readonly Config $config,
    ) {}

    public function execute(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->cancelOrdersForStore((int) $store->getId());
        }
    }

    private function cancelOrdersForStore(int $storeId): void
    {
        foreach ($this->expiredPaymentLinkOrders->getForStore($storeId) as $order) {
            $this->cancelOrderAndLogFailure($order);
        }
    }

    private function cancelOrderAndLogFailure(OrderInterface $order): void
    {
        try {
            $this->cancel($order);
        } catch (Throwable $exception) {
            $this->config->addToLog('error', [
                'message' => 'Error while canceling an expired payment link order',
                'order_id' => $order->getEntityId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function cancel(OrderInterface $order): void
    {
        $transactionId = (string) $order->getMollieTransactionId();
        if ($transactionId === '') {
            $this->cancelOrder->execute($order, self::CANCEL_REASON);

            return;
        }

        $this->processTransaction->execute((int) $order->getEntityId(), $transactionId);
    }
}
