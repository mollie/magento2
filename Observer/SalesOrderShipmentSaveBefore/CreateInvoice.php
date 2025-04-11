<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesOrderShipmentSaveBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Service\Mollie\Order\UsedMollieApi;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\PartialInvoice;

class CreateInvoice implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var UsedMollieApi
     */
    private $usedMollieApi;
    /**
     * @var PartialInvoice
     */
    private $partialInvoice;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var ShouldEmailInvoice
     */
    private $shouldEmailInvoice;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    public function __construct(
        Config $config,
        UsedMollieApi $usedMollieApi,
        PartialInvoice $partialInvoice,
        OrderRepositoryInterface $orderRepository,
        ShouldEmailInvoice $shouldEmailInvoice,
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory
    ) {
        $this->config = $config;
        $this->usedMollieApi = $usedMollieApi;
        $this->partialInvoice = $partialInvoice;
        $this->orderRepository = $orderRepository;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        if ($this->config->getInvoiceMoment($order->getStoreId()) != InvoiceMoment::ON_SHIPMENT ||
            $this->usedMollieApi->execute($order) != UsedMollieApi::TYPE_PAYMENTS
        ) {
            return;
        }

        $payment = $order->getPayment();
        if ($payment->getMethod() != Creditcard::CODE || $payment->getIsTransactionClosed()) {
            return;
        }

        $invoice = $this->partialInvoice->createFromShipment($shipment);

        $this->orderRepository->save($order);

        $sendInvoice = $this->shouldEmailInvoice->execute((int)$order->getStoreId(), $payment->getMethod());
        if ($invoice && $invoice->getId() && !$invoice->getEmailSent() && $sendInvoice) {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $this->orderCommentHistory->add($order, $message, true);
        }
    }
}
