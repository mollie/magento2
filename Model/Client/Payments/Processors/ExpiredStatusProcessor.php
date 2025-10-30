<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;

class ExpiredStatusProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private ExpiredOrderToTransaction $expiredOrderToTransaction,
        private FailedStatusProcessor $failedStatusProcessor,
        private ProcessTransactionResponseFactory $processTransactionResponseFactory
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        if ($this->shouldCancelProcessing($magentoOrder)) {
            return $this->processTransactionResponseFactory->create([
                'success' => false,
                'status' => $molliePayment->status,
                'order_id' => $magentoOrder->getEntityId(),
                'type' => $type,
            ]);
        }

        return $this->failedStatusProcessor->process($magentoOrder, $molliePayment, $type, $response);
    }

    private function shouldCancelProcessing(OrderInterface $order): bool
    {
        if (!$this->expiredOrderToTransaction->hasMultipleTransactions($order)) {
            return false;
        }

        $this->expiredOrderToTransaction->markTransactionAsSkipped($order->getMollieTransactionId());

        return true;
    }
}
