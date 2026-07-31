<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Service\Mollie\Customer\ForgetMollieCustomerId;
use Mollie\Payment\Service\Mollie\Customer\MollieCustomerNoLongerExists;
use Mollie\Payment\Service\OrderLockService;

class StartTransaction
{
    public function __construct(
        private ManagerInterface $eventManager,
        private OrderLockService $orderLockService,
        private Timeout $timeout,
        private PaymentsApi $paymentsApi,
        private MollieApiClient $mollieApiClient,
        private MollieCustomerNoLongerExists $mollieCustomerNoLongerExists,
        private ForgetMollieCustomerId $forgetMollieCustomerId,
        private Config $config,
    ) {
    }

    public function execute(OrderInterface $order): ?string
    {
        $this->eventManager->dispatch('mollie_start_transaction', ['order' => $order]);

        return $this->orderLockService->execute($order, function (OrderInterface $order): ?string {
            /** @var Payment $payment */
            $payment = $order->getPayment();

            // When clicking the back button from the hosted payment we need a way to verify if the order was paid or not.
            // If this is not the case, we restore the quote. This flag is used to determine if it was paid or not.
            $payment->setAdditionalInformation('mollie_success', false);

            try {
                return $this->sendTransaction($order);
            } catch (ApiException $exception) {
                return $this->retryWithNewMollieCustomer($order, $exception);
            }
        });
    }

    private function sendTransaction(OrderInterface $order): ?string
    {
        $mollieApi = $this->mollieApiClient->loadByStore(storeId($order->getStoreId()));

        return $this->timeout->retry(fn (): ?string => $this->paymentsApi->startTransaction($order, $mollieApi));
    }

    private function retryWithNewMollieCustomer(OrderInterface $order, ApiException $exception): ?string
    {
        if (!$this->mollieCustomerNoLongerExists->check($exception)) {
            throw $exception;
        }

        if (!$this->forgetMollieCustomerId->execute($order)) {
            throw $exception;
        }

        $this->config->addToLog('info', sprintf(
            'The Mollie customer used for order %s no longer exists. ' .
            'Removed the stored customer and retried the transaction.',
            $order->getIncrementId(),
        ));

        return $this->sendTransaction($order);
    }
}
