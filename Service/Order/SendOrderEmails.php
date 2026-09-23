<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;
use Throwable;

class SendOrderEmails
{
    private const XML_PATH_ASYNC_SENDING = 'sales_email/general/async_sending';

    private bool $disableOrderConfirmationSending = false;

    private bool $disableInvoiceSending = false;

    public function __construct(
        private OrderSender $orderSender,
        private OrderCommentHistory $orderCommentHistory,
        private InvoiceSender $invoiceSender,
        private ShouldEmailInvoice $shouldEmailInvoice,
        private ScopeConfigInterface $scopeConfig
    ) {}

    public function disableOrderConfirmationSending(): void
    {
        $this->disableOrderConfirmationSending = true;

    }

    /**
     * @param OrderInterface|Order $order
     */
    public function sendOrderConfirmation(OrderInterface $order): void
    {
        if ($order->getEmailSent() || $this->disableOrderConfirmationSending) {
            return;
        }

        try {
            /** @var Order $order */
            $isSent = $this->orderSender->send($order);
        } catch (Throwable $exception) {
            $message = __('Unable to send the new order email: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, false);
            return;
        }

        $this->addOrderConfirmationComment($order, $isSent);
    }

    /**
     * @param OrderInterface|Order $order
     */
    private function addOrderConfirmationComment(OrderInterface $order, bool $isSent): void
    {
        if ($isSent) {
            $this->orderCommentHistory->add($order, __('New order email sent'), true);
            return;
        }

        if (!$order->getSendEmail()) {
            return;
        }

        if ($this->isAsyncSendingEnabled()) {
            $this->orderCommentHistory->add($order, __('New order email scheduled for sending'), false);
            return;
        }

        $this->orderCommentHistory->add(
            $order,
            __('Unable to send the new order email, please check the logs for details'),
            false
        );
    }

    private function isAsyncSendingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ASYNC_SENDING);
    }

    public function disableInvoiceSending(): void
    {
        $this->disableInvoiceSending = true;
    }

    public function sendInvoiceEmail(InvoiceInterface $invoice): void
    {
        $order = $invoice->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();

        if (
            $invoice->getEmailSent() ||
            !$this->shouldEmailInvoice->execute((int) $invoice->getStoreId(), $paymentMethod) ||
            $this->disableInvoiceSending
        ) {
            return;
        }

        try {
            /** @var Invoice $invoice */
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        } catch (Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }
}
