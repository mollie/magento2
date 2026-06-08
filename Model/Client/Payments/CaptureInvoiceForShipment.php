<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;

class CaptureInvoiceForShipment
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceService $invoiceService,
        private readonly CapturePaymentForInvoice $capturePaymentForInvoice,
    ) {}

    public function execute(ShipmentInterface $shipment): ?InvoiceInterface
    {
        $order = $shipment->getOrder();

        if (!$order->canInvoice()) {
            return null;
        }

        $invoice = $this->invoiceService->prepareInvoice($order, $this->getShipmentQtys($shipment));
        $invoice->register();

        $this->invoiceRepository->save($invoice);

        $this->capturePaymentForInvoice->execute($invoice);

        return $invoice;
    }

    private function getShipmentQtys(ShipmentInterface $shipment): array
    {
        $qtys = [];
        foreach ($shipment->getItems() as $item) {
            $qtys[$item->getOrderItemId()] = $item->getQty();
        }
        return $qtys;
    }
}
