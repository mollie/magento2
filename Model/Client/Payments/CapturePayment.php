<?php

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
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

    public function __construct(
        MollieApiClient $mollieApiClient,
        PartialInvoice $partialInvoice,
        General $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        ShouldEmailInvoice $shouldEmailInvoice
    ) {
        $this->mollieApiClient = $mollieApiClient;
        $this->partialInvoice = $partialInvoice;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
    }

    public function execute(ShipmentInterface $shipment, OrderInterface $order): void
    {
        $payment = $order->getPayment();
        $invoice = $this->partialInvoice->createFromShipment($shipment);
        if (!$invoice) {
            return;
        }

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
        $payment->registerCaptureNotification($captureAmount, true);

        $this->orderRepository->save($order);

        $sendInvoice = $this->shouldEmailInvoice->execute($order->getStoreId(), $payment->getMethod());
        if ($invoice && $invoice->getId() && !$invoice->getEmailSent() && $sendInvoice) {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }
}
