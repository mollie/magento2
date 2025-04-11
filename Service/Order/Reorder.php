<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Catalog\Helper\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\SecondChancePaymentMethod;
use Mollie\Payment\Plugin\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsSalableWithReservationsCondition\DisableCheckForAdminOrders;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;

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
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Product
     */
    private $productHelper;

    /**
     * @var DisableCheckForAdminOrders
     */
    private $disableCheckForAdminOrders;

    /**
     * @var ShouldEmailInvoice
     */
    private $shouldEmailInvoice;

    public function __construct(
        Config $config,
        Create $orderCreate,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        TransactionFactory $transactionFactory,
        Session $checkoutSession,
        Product $productHelper,
        DisableCheckForAdminOrders $disableCheckForAdminOrders,
        ShouldEmailInvoice $shouldEmailInvoice
    ) {
        $this->config = $config;
        $this->orderCreate = $orderCreate;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->transactionFactory = $transactionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->productHelper = $productHelper;
        $this->disableCheckForAdminOrders = $disableCheckForAdminOrders;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
    }

    public function create(OrderInterface $originalOrder): OrderInterface
    {
        $this->transaction = $this->transactionFactory->create();

        $order = $this->recreate(
            $originalOrder,
            $this->getPaymentMethod($originalOrder)
        );

        $this->cancelOriginalOrder($originalOrder);

        $this->transaction->save();

        $this->addCommentHistoryOriginalOrder($originalOrder, $order->getIncrementId());

        $this->checkoutSession->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId());

        return $order;
    }

    public function createAndInvoice(
        OrderInterface $originalOrder,
        ?string $state = null,
        ?string $status = null
    ): OrderInterface {
        $this->transaction = $this->transactionFactory->create();

        $order = $this->recreate($originalOrder);
        $invoice = $this->createInvoiceFor($order);
        $this->cancelOriginalOrder($originalOrder);

        if ($state && $status) {
            $order->setState($state);
            $order->setStatus($status);
        }

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
    private function recreate(
        OrderInterface $originalOrder,
        string $method = 'mollie_methods_reorder'
    ): OrderInterface {
        $originalOrder->setReordered(true);
        $session = $this->orderCreate->getSession();
        $session->clearStorage();
        $session->setOrderId($originalOrder->getEntityId());
        $session->setUseOldShippingMethod(true);
        $this->orderCreate->setPaymentMethod($method);
        $cart = $this->orderCreate->getQuote();
        $cart->setCustomerId($originalOrder->getCustomerId());
        $cart->setCustomerIsGuest($originalOrder->getCustomerIsGuest());

        $this->disableCheckForAdminOrders->disable();
        $this->productHelper->setSkipSaleableCheck(true);
        $this->orderCreate->setData('account', ['email' => $originalOrder->getCustomerEmail()]);
        $this->orderCreate->initFromOrder($originalOrder);

        $customerGroupId = $originalOrder->getCustomerGroupId() ?? 0;
        $this->orderCreate->getQuote()->getCustomer()->setGroupId($customerGroupId);

        $order = $this->orderCreate->createOrder();

        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);

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
        if ($invoice->getEmailSent() ||
            !$this->shouldEmailInvoice->execute((int)$order->getStoreId(), $order->getPayment()->getMethod())
        ) {
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

    /**
     * @param OrderInterface $originalOrder
     * @return string|null
     */
    public function getPaymentMethod(OrderInterface $originalOrder): ?string
    {
        $value = $this->config->secondChanceUsePaymentMethod($originalOrder->getStoreId());

        if ($value == SecondChancePaymentMethod::USE_PREVIOUS_METHOD) {
            return $originalOrder->getPayment()->getMethod();
        }

        return $value;
    }
}
