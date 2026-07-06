<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Mollie\Payment\Observer\LockUnlockOrder;
use Mollie\Payment\Service\LockService;
use Mollie\Payment\Test\Fakes\Framework\Lock\LockManagerFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LockUnlockOrderTest extends IntegrationTestCase
{
    public function testTheObserverIsRegisteredForTheTrackSaveAfterEvent(): void
    {
        /** @var ConfigInterface $eventConfig */
        $eventConfig = $this->objectManager->get(ConfigInterface::class);

        $instances = array_map(
            static fn (array $observer): string => ltrim(strtolower($observer['instance'] ?? ''), '\\'),
            $eventConfig->getObservers('sales_order_shipment_track_save_after')
        );

        $this->assertContains(strtolower(LockUnlockOrder::class), $instances);
    }

    public function testReleasesTheLockWhenTheTrackSaveAfterEventIsHandled(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->objectManager->create(LockService::class, ['lockManager' => $lockManager]);

        /** @var LockUnlockOrder $observer */
        $observer = $this->objectManager->create(LockUnlockOrder::class, ['lockService' => $lockService]);

        $track = $this->buildTrackForOrderId(999999);
        $key = 'mollie.order.999999';

        $observer->execute($this->buildObserverForEvent('sales_order_shipment_track_save_before', $track));
        $this->assertTrue($lockService->isLocked($key));

        $observer->execute($this->buildObserverForEvent('sales_order_shipment_track_save_after', $track));
        $this->assertFalse($lockService->isLocked($key));
    }

    private function buildTrackForOrderId(int $orderId): Track
    {
        $order = $this->objectManager->create(Order::class);
        $order->setEntityId($orderId);

        $shipment = $this->objectManager->create(Shipment::class);
        $shipment->setOrder($order);

        $track = $this->objectManager->create(Track::class);
        $track->setShipment($shipment);

        return $track;
    }

    private function buildObserverForEvent(string $name, Track $track): Observer
    {
        /** @var Event $event */
        $event = $this->objectManager->create(Event::class);
        $event->setName($name);
        $event->setData('track', $track);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        return $observer;
    }
}
