<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Payment\Service\Order\TransactionProcessor;

class SendEmailForBanktransfer implements PaymentProcessorInterface
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var SendOrderEmails
     */
    private $sendOrderEmails;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        TransactionProcessor $transactionProcessor,
        General $mollieHelper,
        SendOrderEmails $sendOrderEmails
    ) {
        $this->transactionProcessor = $transactionProcessor;
        $this->mollieHelper = $mollieHelper;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->sendOrderEmails = $sendOrderEmails;
    }

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if ($molliePayment->method != 'banktransfer' || $magentoOrder->getEmailSent()) {
            return $response;
        }

        if (!$statusPending = $this->mollieHelper->getStatusPendingBanktransfer($magentoOrder->getStoreId())) {
            $statusPending = $magentoOrder->getStatus();
        }

        $magentoOrder->setStatus($statusPending);
        $magentoOrder->setState(Order::STATE_PENDING_PAYMENT);
        $this->sendOrderEmails->sendOrderConfirmation($magentoOrder);

        $this->transactionProcessor->process($magentoOrder, null, $molliePayment);

        return $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => 'open',
            'order_id' => $magentoOrder->getId(),
            'type' => $type,
        ]);
    }
}
