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
use Mollie\Payment\Helper\General;

class SendOrderEmails
{
    /**
     * @var General
     */
    private $mollieHelper;

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

    public function __construct(
        General $mollieHelper,
        OrderSender $orderSender,
        OrderCommentHistory $orderCommentHistory,
        InvoiceSender $invoiceSender
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->orderSender = $orderSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * @param OrderInterface|Order $order
     */
    public function sendOrderConfirmation(OrderInterface $order): void
    {
        if ($order->getEmailSent()) {
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

    public function sendInvoiceEmail(InvoiceInterface $invoice): void
    {
        if ($invoice->getEmailSent() ||
            !$this->mollieHelper->sendInvoice($invoice->getStoreId())
        ) {
            return;
        }

        try {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($invoice->getOrder(), $message, true);
        } catch (\Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($invoice->getOrder(), $message, true);
        }
    }
}
