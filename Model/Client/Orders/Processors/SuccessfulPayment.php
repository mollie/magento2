<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Api\Resources\Order;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Payment\Service\Order\TransactionProcessor;

class SuccessfulPayment implements OrderProcessorInterface
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SendOrderEmails
     */
    private $sendOrderEmails;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        RequestInterface $request,
        CheckoutSession $checkoutSession,
        General $mollieHelper,
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        TransactionProcessor $transactionProcessor,
        OrderCommentHistory $orderCommentHistory,
        OrderRepositoryInterface $orderRepository,
        SendOrderEmails $sendOrderEmails
    ) {
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->mollieHelper = $mollieHelper;
        $this->invoiceService = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->transactionProcessor = $transactionProcessor;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->orderRepository = $orderRepository;
        $this->sendOrderEmails = $sendOrderEmails;
    }

    /**
     * @param OrderInterface|MagentoOrder $order
     * @param MollieOrder $mollieOrder
     * @param string $type
     * @param ProcessTransactionResponse $response
     * @return ProcessTransactionResponse|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(OrderInterface $order, Order $mollieOrder, string $type, ProcessTransactionResponse $response): ?ProcessTransactionResponse
    {
        $orderId = $order->getEntityId();
        $orderAmount = $this->mollieHelper->getOrderAmountByOrder($order);

        if ($mollieOrder->amount->currency != $orderAmount['currency']) {
            $result = ['success' => false, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('error', __('Currency does not match.'));
            return $this->processTransactionResponseFactory->create($result);
        }

        /** @var false|\Mollie\Api\Resources\Payment $payment */
        $payment = $mollieOrder->payments()->offsetGet(0);
        if ($payment && $payment->hasChargebacks()) {
            $this->orderCommentHistory->add($order,
                __(
                    'Mollie: Received a chargeback with an amount of %1',
                    $order->getBaseCurrency()->formatTxt($payment->getAmountChargedBack())
                )
            );

            $result = ['success' => false, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('error', __('Payment has chargebacks.'));
            return $this->processTransactionResponseFactory->create($result);
        }

        if (!$order->getPayment()->getIsTransactionClosed() && $type == 'webhook') {
            $this->handleWebhookCall($order, $mollieOrder);
            $this->sendOrderEmails($order);
        }

        $result = ['success' => true, 'status' => $mollieOrder->status, 'order_id' => $orderId, 'type' => $type];
        $this->mollieHelper->addTolog('success', $result);
        $this->checkCheckoutSession($order, $mollieOrder, $type);
        return $this->processTransactionResponseFactory->create($result);
    }

    /**
     * @param OrderInterface|MagentoOrder $order
     * @param MollieOrder $mollieOrder
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    private function handleWebhookCall(OrderInterface $order, MollieOrder $mollieOrder): void
    {
        if ($order->isCanceled()) {
            $order = $this->mollieHelper->uncancelOrder($order);
        }

        $orderAmount = $this->mollieHelper->getOrderAmountByOrder($order);
        if (!(abs($mollieOrder->amount->value - $orderAmount['value']) < 0.01)) {
            return;
        }

        $payments = $mollieOrder->_embedded->payments;
        $paymentId = end($payments)->id;

        /** @var OrderPaymentInterface|Payment $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($paymentId);
        $payment->setCurrencyCode($order->getBaseCurrencyCode());

        if (!in_array($order->getState(), [MagentoOrder::STATE_PROCESSING, MagentoOrder::STATE_COMPLETE]) &&
            $mollieOrder->isPaid()
        ) {
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
        }

        if ($mollieOrder->isAuthorized() &&
            $this->mollieHelper->getInvoiceMoment($order->getStoreId()) == InvoiceMoment::ON_AUTHORIZE &&
            $order->getInvoiceCollection()->count() === 0
        ) {
            $payment->setIsTransactionClosed(false);
            $payment->registerAuthorizationNotification($order->getBaseGrandTotal(), true);
            $this->createPendingInvoice($order, $paymentId);
        }

        $order->setState(MagentoOrder::STATE_PROCESSING);
        $this->transactionProcessor->process($order, $mollieOrder);

        if ($mollieOrder->amountCaptured !== null &&
            $mollieOrder->amount->currency != $mollieOrder->amountCaptured->currency
        ) {
            $message = __(
                'Mollie: Order Amount %1, Captured Amount %2',
                $mollieOrder->amount->currency . ' ' . $mollieOrder->amount->value,
                $mollieOrder->amountCaptured->currency . ' ' . $mollieOrder->amountCaptured->value
            );

            $this->orderCommentHistory->add($order, $message);
        }

        $this->setOrderStatus($order);
        $this->orderRepository->save($order);
    }

    public function checkCheckoutSession(
        OrderInterface $order,
        MollieOrder $paymentData,
        string $type
    ): void {
        if ($type == 'webhook') {
            return;
        }

        $paymentToken = $this->request->getParam('payment_token');

        if ($this->checkoutSession->getLastOrderId() != $order->getId()) {
            if ($paymentToken && isset($paymentData->metadata->payment_token)) {
                if ($paymentToken == $paymentData->metadata->payment_token) {
                    $this->checkoutSession->setLastQuoteId($order->getQuoteId())
                        ->setLastSuccessQuoteId($order->getQuoteId())
                        ->setLastOrderId($order->getId())
                        ->setLastRealOrderId($order->getIncrementId());
                }
            }
        }
    }

    /**
     * @param OrderInterface|MagentoOrder $order
     */
    protected function sendOrderEmails(OrderInterface $order): void
    {
        $this->sendOrderEmails->sendOrderConfirmation($order);

        if ($invoice = $order->getPayment()->getCreatedInvoice()) {
            $this->sendOrderEmails->sendInvoiceEmail($invoice);
        }
    }

    /**
     * @param OrderInterface $order
     * @param $paymentId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    private function createPendingInvoice(OrderInterface $order, $paymentId): void
    {
        /**
         * Create pending invoice, as order has not been paid.
         */
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
        $invoice->setTransactionId($paymentId);
        $invoice->register();

        $this->invoiceRepository->save($invoice);
        $order->getPayment()->setCreatedInvoice($invoice);
    }

    /**
     * @param $order
     * @return void
     */
    private function setOrderStatus($order): void
    {
        $defaultStatusProcessing = $this->mollieHelper->getStatusProcessing($order->getStoreId());
        if (!$order->getIsVirtual() &&
            $defaultStatusProcessing &&
            $defaultStatusProcessing != $order->getStatus()
        ) {
            $order->setStatus($defaultStatusProcessing);
        }
    }
}
