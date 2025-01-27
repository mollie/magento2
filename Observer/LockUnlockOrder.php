<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Mollie\Payment\Service\LockService;

class LockUnlockOrder implements ObserverInterface
{
    /**
     * @var LockService
     */
    private $lockService;

    /**
     * @var string
     */
    private $reason = '';

    public function __construct(
        LockService $lockService
    ) {
        $this->lockService = $lockService;
    }

    public function execute(Observer $observer)
    {
        $name = $observer->getEvent()->getName();
        $order = $this->getOrder($name, $observer);

        $key = 'mollie.order.' . $order->getEntityId();
        if (strpos($name, 'save_before') !== false) {
            if ($this->lockService->checkIfIsLockedWithWait($key)) {
                throw new LocalizedException(__('Unable to get lock for %1', $key));
            }

            $this->lockService->lock($key, -1, $this->reason);
        }

        if (strpos($name, 'save_after') !== false) {
            $this->lockService->unlock($key);
        }
    }

    private function getOrder(string $name, Observer $observer): OrderInterface
    {
        /** @var ShipmentInterface $shipment */
        $shipment = $observer->getEvent()->getData('shipment');

        if ($shipment) {
            $this->reason = 'shipment';
            return $shipment->getOrder();
        }

        $this->reason = 'shipment tracking';
        $track = $observer->getEvent()->getData('track');
        return $track->getShipment()->getOrder();
    }
}
