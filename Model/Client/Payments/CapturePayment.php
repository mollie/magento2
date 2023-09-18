<?php

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\MollieApiClient;
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

    public function __construct(
        MollieApiClient $mollieApiClient,
        PartialInvoice $partialInvoice,
        General $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        ShouldEmailInvoice $shouldEmailInvoice,
        PriceCurrencyInterface $price
    ) {
        $this->mollieApiClient = $mollieApiClient;
        $this->partialInvoice = $partialInvoice;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
        $this->price = $price;
    }

    public function execute(InvoiceInterface $invoice): void
    {
        $order = $invoice->getOrder();
        $payment = $order->getPayment();

        $order->setState(Order::STATE_PAYMENT_REVIEW);
        $status = $order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW);

        $captureAmount = $invoice->getBaseGrandTotal();

        $mollieTransactionId = $order->getMollieTransactionId();
        $mollieApi = $this->mollieApiClient->loadByStore($order->getStoreId());

        $data = [];
        if ($captureAmount != $order->getBaseGrandTotal()) {
            $data['amount'] = $this->mollieHelper->getAmountArray(
                $order->getOrderCurrencyCode(),
                $captureAmount
            );
        }

        $capture = $mollieApi->paymentCaptures->createForId($mollieTransactionId, $data);
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
}
