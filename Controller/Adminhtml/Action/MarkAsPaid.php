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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

class MarkAsPaid extends Action
{
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
     * @var Transaction
     */
    private $transaction;

    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        Create $orderCreate,
        Quote $quoteSession,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($context);

        $this->orderRepository = $orderRepository;
        $this->orderCreate = $orderCreate;
        $this->quoteSession = $quoteSession;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
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
            $this->createInvoiceFor($order);
            $this->cancelOriginalOrder($originalOrder, $order->getIncrementId());
            $this->transaction->save();

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
     * @param string $newIncrementId
     */
    private function cancelOriginalOrder(OrderInterface $originalOrder, $newIncrementId)
    {
        $comment = __('We created a new order with increment ID: %1', $newIncrementId);
        $originalOrder->addCommentToStatusHistory($comment);
        $originalOrder->cancel();
    }

    private function createInvoiceFor(OrderInterface $order)
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $this->transaction->addObject($invoice);
    }
}
