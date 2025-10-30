<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Service\OrderLockService;

class StartTransaction
{
    public function __construct(
        private ManagerInterface $eventManager,
        private OrderLockService $orderLockService,
        private Timeout $timeout,
        private PaymentsApi $paymentsApi,
        private MollieApiClient $mollieApiClient,
    ) {
    }

    public function execute(OrderInterface $order): ?string
    {
        $this->eventManager->dispatch('mollie_start_transaction', ['order' => $order]);

        return $this->orderLockService->execute($order, function (OrderInterface $order) {
            $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));

            // When clicking the back button from the hosted payment we need a way to verify if the order was paid or not.
            // If this is not the case, we restore the quote. This flag is used to determine if it was paid or not.
            $order->getPayment()->setAdditionalInformation('mollie_success', false);

            return $this->timeout->retry(function () use ($order, $mollieApi): ?string {
                return $this->paymentsApi->startTransaction($order, $mollieApi);
            });
        });
    }
}
