<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesOrderShipmentSaveBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Client\Payments\CaptureInvoiceForShipment;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Mollie\Order\WhenToCapture;

class CaptureShipment implements ObserverInterface
{
    public function __construct(
        private readonly CanUseManualCapture $canUseManualCapture,
        private readonly WhenToCapture $whenToCapture,
        private readonly CaptureInvoiceForShipment $captureInvoiceForShipment
    ) {}

    public function execute(Observer $observer)
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $observer->getEvent()->getShipment();

        /** @var Order $order */
        $order = $shipment->getOrder();

        if (!$this->canUseManualCapture->execute($order)) {
            return;
        }

        $method = $order->getPayment()->getMethod();
        if (!$this->whenToCapture->onShipment($method, storeId($order->getStoreId()))) {
            return;
        }

        $this->captureInvoiceForShipment->execute($shipment);
    }
}
