<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Payment\Config;

class Reorder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Create
     */
    private $orderCreate;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        Config $config,
        Create $orderCreate,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        TransactionFactory $transactionFactory,
        CartInterface $cart,
        Session $checkoutSession
    ) {
        $this->config = $config;
        $this->orderCreate = $orderCreate;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->transactionFactory = $transactionFactory;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
    }

    public function create(OrderInterface $originalOrder)
    {
        $this->transaction = $this->transactionFactory->create();

        $order = $this->recreate($originalOrder, $originalOrder->getPayment()->getMethod());
        $this->cancelOriginalOrder($originalOrder);

        $this->transaction->save();

        $this->addCommentHistoryOriginalOrder($originalOrder, $order->getIncrementId());

        $this->checkoutSession->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId());

        return $order;
    }

    public function createAndInvoice(OrderInterface $originalOrder)
    {
        $this->transaction = $this->transactionFactory->create();

        $order = $this->recreate($originalOrder);
        $invoice = $this->createInvoiceFor($order);
        $this->cancelOriginalOrder($originalOrder);

        $this->transaction->save();

        $this->addCommentHistoryOriginalOrder($originalOrder, $order->getIncrementId());
        $this->sendInvoice($invoice, $order);

        return $order;
    }

    /**
     * @param OrderInterface $originalOrder
     * @param string $method
     * @return OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function recreate(OrderInterface $originalOrder, string $method = 'mollie_methods_reorder')
    {
        $originalOrder->setReordered(true);
        $session = $this->orderCreate->getSession();
        $session->clearStorage();
        $session->setOrderId($originalOrder->getEntityId());
        $session->setUseOldShippingMethod(true);
        $this->orderCreate->setPaymentMethod($method);
        $cart = $this->orderCreate->getQuote();
        $cart->setCustomerId($originalOrder->getCustomerId());
        $cart->setCustomerIsGuest($originalOrder->getCustomerIsGuest());
        $this->orderCreate->initFromOrder($originalOrder);

        $order = $this->orderCreate->createOrder();

        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus(Order::STATE_PROCESSING);

        $this->transaction->addObject($order);
        $this->transaction->addObject($originalOrder);

        return $order;
    }

    /**
     * @param OrderInterface $originalOrder
     */
    private function cancelOriginalOrder(OrderInterface $originalOrder)
    {
        $originalOrder->cancel();
    }

    private function createInvoiceFor(OrderInterface $order)
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $this->transaction->addObject($invoice);

        return $invoice;
    }

    private function sendInvoice(InvoiceInterface $invoice, OrderInterface $order)
    {
        /** @var Order\Invoice $invoice */
        if ($invoice->getEmailSent() || !$this->config->sendInvoiceEmail($invoice->getStoreId())) {
            return;
        }

        try {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        } catch (\Throwable $exception) {
            $message = __('Unable to send the invoice: %1', $exception->getMessage());
            $this->orderCommentHistory->add($order, $message, false);
        }
    }

    /**
     * @param OrderInterface $originalOrder
     * @param string $newIncrementId
     */
    private function addCommentHistoryOriginalOrder(OrderInterface $originalOrder, $newIncrementId)
    {
        $comment = __('We created a new order with increment ID: %1', $newIncrementId);
        $this->orderCommentHistory->add($originalOrder, $comment, false);
    }
}