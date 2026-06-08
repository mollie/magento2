<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Payment\Service\Order\TransactionProcessor;

class SendEmailForBanktransfer implements PaymentProcessorInterface
{
    public function __construct(
        private Config $config,
        private ProcessTransactionResponseFactory $processTransactionResponseFactory,
        private TransactionProcessor $transactionProcessor,
        private SendOrderEmails $sendOrderEmails
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        if ($molliePayment->method != 'banktransfer' || $magentoOrder->getEmailSent()) {
            return $response;
        }

        if (!$statusPending = $this->config->statusPendingBanktransfer($magentoOrder->getStoreId())) {
            $statusPending = $magentoOrder->getStatus();
        }

        $magentoOrder->setStatus($statusPending);
        $magentoOrder->setState(Order::STATE_PENDING_PAYMENT);
        $this->sendOrderEmails->sendOrderConfirmation($magentoOrder);

        $this->transactionProcessor->process($magentoOrder, $molliePayment);

        return $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => 'open',
            'order_id' => $magentoOrder->getId(),
            'type' => $type,
        ]);
    }
}
