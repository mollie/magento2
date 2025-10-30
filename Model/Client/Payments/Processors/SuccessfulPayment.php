<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Mollie\Order\CanRegisterCaptureNotification;
use Mollie\Payment\Service\Order\OrderAmount;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Payment\Service\Order\TransactionProcessor;
use Mollie\Payment\Service\Order\Uncancel;

class SuccessfulPayment implements PaymentProcessorInterface
{
    public function __construct(
        private Config $config,
        private ProcessTransactionResponseFactory $processTransactionResponseFactory,
        private OrderAmount $orderAmount,
        private Uncancel $uncancel,
        private TransactionProcessor $transactionProcessor,
        private OrderCommentHistory $orderCommentHistory,
        private General $mollieHelper,
        private OrderRepositoryInterface $orderRepository,
        private SendOrderEmails $sendOrderEmails,
        private CanRegisterCaptureNotification $canRegisterCaptureNotification,
    ) {
    }

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        $amount = $molliePayment->amount->value;
        $currency = $molliePayment->amount->currency;

        if ($molliePayment->hasChargebacks()) {
            $this->orderCommentHistory->add(
                $magentoOrder,
                __(
                    'Mollie: Received a chargeback with an amount of %1',
                    $magentoOrder->getBaseCurrency()->formatTxt($molliePayment->getAmountChargedBack()),
                ),
            );

            return $this->processTransactionResponseFactory->create([
                'success' => false,
                'status' => 'chargeback',
                'order_id' => $magentoOrder->getId(),
                'type' => $type,
            ]);
        }

        $orderAmount = $this->orderAmount->getByTransactionId($magentoOrder->getMollieTransactionId());
        if ($currency != $orderAmount['currency']) {
            return $this->processTransactionResponseFactory->create([
                'success' => false,
                'status' => 'paid',
                'order_id' => $magentoOrder->getId(),
                'type' => $type,
            ]);
        }

        /** @var PaymentInterface|Order\Payment $payment */
        $payment = $magentoOrder->getPayment();
        if ($payment->getIsTransactionClosed() || $type != 'webhook') {
            return $this->processTransactionResponseFactory->create([
                'success' => true,
                'status' => 'paid',
                'order_id' => $magentoOrder->getId(),
                'type' => $type,
            ]);
        }

        if ($magentoOrder->isCanceled()) {
            $this->uncancel->execute($magentoOrder);
        }

        if (abs($amount - $orderAmount['value']) < 0.01) {
            $this->handlePayment($magentoOrder, $molliePayment, $type);
        }

        /** @var Order\Invoice|null $invoice */
        $invoice = $payment->getCreatedInvoice();
        $sendInvoice = $this->config->sendInvoiceEmail((int) $magentoOrder->getStoreId());

        $this->sendOrderConfirmationEmail($magentoOrder);
        if ($invoice) {
            $this->sendInvoiceEmail($invoice, $sendInvoice, $magentoOrder);
        }

        return $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => 'paid',
            'order_id' => $magentoOrder->getId(),
            'type' => $type,
        ]);
    }

    private function handlePayment(OrderInterface $magentoOrder, Payment $molliePayment, string $type): void
    {
        /** @var PaymentInterface|Order\Payment $payment */
        $payment = $magentoOrder->getPayment();
        $payment->setCurrencyCode($magentoOrder->getBaseCurrencyCode());
        $payment->setTransactionId($magentoOrder->getMollieTransactionId());
        $payment->setIsTransactionClosed(true);

        if (
            $this->canRegisterCaptureNotification->execute($magentoOrder, $molliePayment) &&
            $type != Payments::TRANSACTION_TYPE_SUBSCRIPTION
        ) {
            if ($molliePayment->getAmountCaptured() != 0.0) {
                $magentoOrder->addCommentToStatusHistory(
                    __(
                        'Successfully captured amount of %1.',
                        $magentoOrder->getBaseCurrency()->formatTxt($molliePayment->getAmountCaptured()),
                    ),
                );
            }

            $payment->registerCaptureNotification($magentoOrder->getBaseGrandTotal(), true);
        }

        $magentoOrder->setState(Order::STATE_PROCESSING);
        $this->transactionProcessor->process($magentoOrder, $molliePayment);

        if ($molliePayment->settlementAmount !== null) {
            if ($molliePayment->amount->currency != $molliePayment->settlementAmount->currency) {
                $message = __(
                    'Mollie: Captured %1, Settlement Amount %2',
                    $molliePayment->amount->currency . ' ' . $molliePayment->amount->value,
                    $molliePayment->settlementAmount->currency . ' ' . $molliePayment->settlementAmount->value,
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

        $this->sendOrderEmails->sendOrderConfirmation($order);
    }

    private function sendInvoiceEmail(Invoice $invoice, bool $sendInvoice, OrderInterface $order): void
    {
        if ($invoice->getEmailSent() || !$sendInvoice) {
            return;
        }

        $this->sendOrderEmails->sendInvoiceEmail($invoice);
    }
}
