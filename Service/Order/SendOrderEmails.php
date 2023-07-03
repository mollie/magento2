<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;

class SendOrderEmails
{
    /**
     * @var bool
     */
    private $disableOrderConfirmationSending = false;

    /**
     * @var bool
     */
    private $disableInvoiceSending = false;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var ShouldEmailInvoice
     */
    private $shouldEmailInvoice;

    public function __construct(
        OrderSender $orderSender,
        OrderCommentHistory $orderCommentHistory,
        InvoiceSender $invoiceSender,
        ShouldEmailInvoice $shouldEmailInvoice
    ) {
        $this->orderSender = $orderSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->invoiceSender = $invoiceSender;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
    }

    public function disableOrderConfirmationSending(): void
    {
        $this->disableOrderConfirmationSending = true;;
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
        } catch (\Throwable $exception) {
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

        if ($invoice->getEmailSent() ||
            !$this->shouldEmailInvoice->execute((int)$invoice->getStoreId(), $paymentMethod) ||
            $this->disableInvoiceSending
        ) {
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
