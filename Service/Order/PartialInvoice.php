<?php

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Payment\Service\Mollie\Order\CreateInvoiceOnShipment;

class PartialInvoice
{
    /**
     * @var CreateInvoiceOnShipment
     */
    private $createInvoiceOnShipment;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    public function __construct(
        CreateInvoiceOnShipment $createInvoiceOnShipment,
        InvoiceService $invoiceService,
        InvoiceRepository $invoiceRepository
    ) {
        $this->invoiceService = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->createInvoiceOnShipment = $createInvoiceOnShipment;
    }

    public function createFromShipment(ShipmentInterface $shipment)
    {
        /** @var OrderInterface $order */
        $order = $shipment->getOrder();

        if (!$this->createInvoiceOnShipment->execute($order)) {
            return null;
        }

        if (!$order->canInvoice()) {
            return null;
        }

        $quantities = [];
        /** @var ShipmentItemInterface $item */
        foreach ($shipment->getAllItems() as $item) {
            $quantities[$item->getOrderItemId()] = $item->getQty();
        }

        $invoice = $this->invoiceService->prepareInvoice($order, $quantities);
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->setState(Invoice::STATE_OPEN);
        $invoice->setTransactionId($order->getMollieTransactionId() . '-' . $shipment->getMollieShipmentId());
        $invoice->register();

        $this->invoiceRepository->save($invoice);

        return $invoice;
    }
}
