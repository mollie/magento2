<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\OrderCommentHistory;

class MarkAsPaid extends Action
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Create
     */
    private $orderCreate;

    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var Transaction
     */
    private $transaction;

    public function __construct(
        Action\Context $context,
        Config $config,
        OrderRepositoryInterface $orderRepository,
        Create $orderCreate,
        Quote $quoteSession,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->orderCreate = $orderCreate;
        $this->quoteSession = $quoteSession;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->orderCommentHistory = $orderCommentHistory;
    }

    /**
     * This controller recreates the selected order with the checkmo payment method and marks it as completed. The
     * original order is then canceled.
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        $originalOrder = $this->orderRepository->get($orderId);
        $originalOrder->setReordered(true);

        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $this->transaction = $this->transactionFactory->create();

            $order = $this->recreateOrder($originalOrder);
            $invoice = $this->createInvoiceFor($order);
            $this->cancelOriginalOrder($originalOrder);
            $this->transaction->save();

            $this->addCommentHistoryOriginalOrder($originalOrder, $order->getIncrementId());
            $this->sendInvoice($invoice, $order);

            $this->messageManager->addSuccessMessage(
                __(
                    'We cancelled order %1, created this order and marked it as complete.',
                    $originalOrder->getIncrementId()
                )
            );

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getEntityId()]);
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage($exception);

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $originalOrder->getEntityId()]);
        }
    }

    /**
     * @param OrderInterface $originalOrder
     * @return OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function recreateOrder(OrderInterface $originalOrder)
    {
        $this->quoteSession->setOrderId($originalOrder->getEntityId());
        $this->quoteSession->setUseOldShippingMethod(true);
        $this->orderCreate->initFromOrder($originalOrder);
        $this->orderCreate->setPaymentMethod('mollie_methods_reorder');

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

    /**
     * @param OrderInterface $originalOrder
     * @param string $newIncrementId
     */
    private function addCommentHistoryOriginalOrder(OrderInterface $originalOrder, $newIncrementId)
    {
        $comment = __('We created a new order with increment ID: %1', $newIncrementId);
        $this->orderCommentHistory->add($originalOrder, $comment, false);
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
}