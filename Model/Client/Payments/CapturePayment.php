<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\UsedMollieApi;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\PartialInvoice;

class CapturePayment
{
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var UsedMollieApi
     */
    private $usedMollieApi;

    /**
     * @var PartialInvoice
     */
    private $partialInvoice;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;
    /**
     * @var ShouldEmailInvoice
     */
    private $shouldEmailInvoice;
    /**
     * @var PriceCurrencyInterface
     */
    private $price;
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    public function __construct(
        MollieApiClient $mollieApiClient,
        UsedMollieApi $usedMollieApi,
        PartialInvoice $partialInvoice,
        General $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        ShouldEmailInvoice $shouldEmailInvoice,
        PriceCurrencyInterface $price,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->mollieApiClient = $mollieApiClient;
        $this->usedMollieApi = $usedMollieApi;
        $this->partialInvoice = $partialInvoice;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
        $this->price = $price;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function execute(InvoiceInterface $invoice): void
    {
        // Otherwise the ID isn't available yet
        $this->invoiceRepository->save($invoice);

        $order = $invoice->getOrder();
        $payment = $order->getPayment();

        $order->setState(Order::STATE_PAYMENT_REVIEW);
        $status = $order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW);

        $captureAmount = $invoice->getGrandTotal();

        $mollieApi = $this->mollieApiClient->loadByStore($order->getStoreId());

        $data = ['metadata' => ['invoice_id' => $invoice->getEntityId()]];
        if ($captureAmount != $order->getBaseGrandTotal()) {
            $data['amount'] = $this->mollieHelper->getAmountArray(
                $order->getOrderCurrencyCode(),
                $captureAmount
            );
        }

        $capture = $this->createCapture($mollieApi, $order, $data);
        $payment->setTransactionId($capture->id);

        $order->addCommentToStatusHistory(
            __(
                'Trying to capture %1. Capture ID: %2',
                $this->price->format($captureAmount),
                $capture->id
            ),
            $status
        );
    }

    private function createCapture(\Mollie\Api\MollieApiClient $mollieApi, OrderInterface $order, array $data): \Mollie\Api\Resources\Capture
    {
        $mollieTransactionId = $order->getMollieTransactionId();
        if ($this->usedMollieApi->execute($order) == UsedMollieApi::TYPE_PAYMENTS) {
            return $mollieApi->paymentCaptures->createForId($mollieTransactionId, $data);
        }

        $mollieOrder = $mollieApi->orders->get($mollieTransactionId);
        $payments = $mollieOrder->payments();
        /** @var Payment $last */
        $last = end($payments);

        return $mollieApi->paymentCaptures->createForId($last->id, $data);
    }
}
