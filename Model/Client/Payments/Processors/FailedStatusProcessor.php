<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

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
    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    public function __construct(
        CancelOrder $cancelOrder,
        General $mollieHelper,
        TransactionProcessor $transactionProcessor,
        ProcessTransactionResponseFactory $processTransactionResponseFactory
    ) {
        $this->cancelOrder = $cancelOrder;
        $this->mollieHelper = $mollieHelper;
        $this->transactionProcessor = $transactionProcessor;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
    }

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        $status = $molliePayment->status;
        if ($type == 'webhook') {
            $this->cancelOrder->execute($magentoOrder, $status);
            $this->transactionProcessor->process($magentoOrder, null, $molliePayment);
        }

        $message = [
            'success' => false,
            'status' => $status,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type
        ];

        $this->mollieHelper->addTolog('success', $message);

        return $this->processTransactionResponseFactory->create($message);
    }
}
