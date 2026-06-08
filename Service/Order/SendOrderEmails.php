<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;
use Throwable;

class SendOrderEmails
{
    private bool $disableOrderConfirmationSending = false;

    private bool $disableInvoiceSending = false;

    public function __construct(
        private OrderSender $orderSender,
        private OrderCommentHistory $orderCommentHistory,
        private InvoiceSender $invoiceSender,
        private ShouldEmailInvoice $shouldEmailInvoice
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
            $this->orderSender->send($order, true);
            $message = __('New order email sent');
            $this->orderCommentHistory->add($order, $message, true);
        } catch (Throwable $exception) {
            $message = __('Unable to send the new order email: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, false);
        }
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
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        } catch (Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }
}
