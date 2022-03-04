<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\TransactionProcessor;

class SendEmailForBanktransfer implements PaymentProcessorInterface
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var General
     */
    private $mollieHelper;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        OrderSender $orderSender,
        TransactionProcessor $transactionProcessor,
        General $mollieHelper
    ) {
        $this->orderSender = $orderSender;
        $this->transactionProcessor = $transactionProcessor;
        $this->mollieHelper = $mollieHelper;
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
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

        try {
            $this->orderSender->send($magentoOrder);
            $message = __('New order email sent');
        } catch (\Throwable $exception) {
            $message = __('Unable to send the new order email: %1', $exception->getMessage());
        }

        if (!$statusPending = $this->mollieHelper->getStatusPendingBanktransfer($magentoOrder->getStoreId())) {
            $statusPending = $magentoOrder->getStatus();
        }
        $magentoOrder->setState(Order::STATE_PENDING_PAYMENT);
        $this->transactionProcessor->process($magentoOrder, null, $molliePayment);
        $magentoOrder->addStatusToHistory($statusPending, $message, true);

        return $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => 'open',
            'order_id' => $magentoOrder->getId(),
            'type' => $type,
        ]);
    }
}
