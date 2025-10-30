<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Service\Order\TransactionProcessor;

class FailedStatusProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private CancelOrder $cancelOrder,
        private General $mollieHelper,
        private TransactionProcessor $transactionProcessor,
        private ProcessTransactionResponseFactory $processTransactionResponseFactory
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        $status = $molliePayment->status;
        if ($type == 'webhook') {
            $this->cancelOrder->execute($magentoOrder, $status);
            $this->transactionProcessor->process($magentoOrder, $molliePayment);
        }

        $message = [
            'success' => false,
            'status' => $status,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type,
        ];

        $this->mollieHelper->addTolog('success', $message);

        return $this->processTransactionResponseFactory->create($message);
    }
}
