<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\OrderAmount;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\TransactionProcessor;
use Mollie\Payment\Service\Order\Uncancel;

class SuccessfulPayment implements PaymentProcessorInterface
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var OrderAmount
     */
    private $orderAmount;

    /**
     * @var Uncancel
     */
    private $uncancel;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Order\Email\Sender\OrderSender
     */
    private $orderSender;

    /**
     * @var Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        OrderAmount $orderAmount,
        Uncancel $uncancel,
        TransactionProcessor $transactionProcessor,
        OrderCommentHistory $orderCommentHistory,
        General $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        Order\Email\Sender\OrderSender $orderSender,
        Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->orderAmount = $orderAmount;
        $this->uncancel = $uncancel;
        $this->transactionProcessor = $transactionProcessor;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
    }

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        $amount = $molliePayment->amount->value;
        $currency = $molliePayment->amount->currency;

        $orderAmount = $this->orderAmount->getByTransactionId($magentoOrder->getMollieTransactionId());
        if ($currency != $orderAmount['currency']) {
            return $this->processTransactionResponseFactory->create([
                'success' => false,
                'status' => 'paid',
                'order_id' => $magentoOrder->getId(),
                'type' => $type
            ]);
        }

        /** @var PaymentInterface|Order\Payment $payment */
        $payment = $magentoOrder->getPayment();
        if ($payment->getIsTransactionClosed() || $type != 'webhook') {
            return $this->processTransactionResponseFactory->create([
                'success' => true,
                'status' => 'paid',
                'order_id' => $magentoOrder->getId(),
                'type' => $type
            ]);
        }

        if ($magentoOrder->isCanceled()) {
            $this->uncancel->execute($magentoOrder);
        }

        if (abs($amount - $orderAmount['value']) < 0.01) {
            $this->handlePayment($magentoOrder, $molliePayment);
        }

        /** @var Order\Invoice|null $invoice */
        $invoice = $payment->getCreatedInvoice();
        $sendInvoice = $this->mollieHelper->sendInvoice($magentoOrder->getStoreId());

        $this->sendOrderConfirmationEmail($magentoOrder);
        if ($invoice) {
            $this->sendInvoiceEmail($invoice, $sendInvoice, $magentoOrder);
        }

        return $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => 'paid',
            'order_id' => $magentoOrder->getId(),
            'type' => $type
        ]);
    }

    private function handlePayment(OrderInterface $magentoOrder, Payment $molliePayment): void
    {
        /** @var PaymentInterface|Order\Payment $payment */
        $payment = $magentoOrder->getPayment();
        $payment->setCurrencyCode($magentoOrder->getBaseCurrencyCode());
        $payment->setTransactionId($magentoOrder->getMollieTransactionId());
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($magentoOrder->getBaseGrandTotal(), true);
        $magentoOrder->setState(Order::STATE_PROCESSING);
        $this->transactionProcessor->process($magentoOrder, null, $molliePayment);

        if ($molliePayment->settlementAmount !== null) {
            if ($molliePayment->amount->currency != $molliePayment->settlementAmount->currency) {
                $message = __(
                    'Mollie: Captured %1, Settlement Amount %2',
                    $molliePayment->amount->currency . ' ' . $molliePayment->amount->value,
                    $molliePayment->settlementAmount->currency . ' ' . $molliePayment->settlementAmount->value
                );
                $this->orderCommentHistory->add($magentoOrder, $message);
            }
        }

        if (!$magentoOrder->getIsVirtual()) {
            $defaultStatusProcessing = $this->mollieHelper->getStatusProcessing($magentoOrder->getStoreId());
            if ($defaultStatusProcessing && ($defaultStatusProcessing != $magentoOrder->getStatus())) {
                $magentoOrder->setStatus($defaultStatusProcessing);
            }
        }

        $this->orderRepository->save($magentoOrder);
    }

    /**
     * @param OrderInterface|Order $order
     */
    private function sendOrderConfirmationEmail(OrderInterface $order): void
    {
        if ($order->getEmailSent()) {
            return;
        }

        try {
            $this->orderSender->send($order);
            $message = __('New order email sent');
            $this->orderCommentHistory->add($order, $message, true);
        } catch (\Throwable $exception) {
            $message = __('Unable to send the new order email: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, false);
        }
    }

    private function sendInvoiceEmail(Order\Invoice $invoice, bool $sendInvoice, OrderInterface $order): void
    {
        if ($invoice->getEmailSent() || !$sendInvoice) {
            return;
        }

        try {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        } catch (\Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }
}
