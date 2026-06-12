<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\LayoutLoadBefore;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Mollie\Payment\Observer\LayoutLoadBefore\AddExpressComponentsLayoutHandle;
use Mollie\Payment\Service\Mollie\ExpressComponentsAvailability;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AddExpressComponentsLayoutHandleTest extends IntegrationTestCase
{
    private const HANDLE = 'mollie_express_components';

    public function testAddsTheLayoutHandleWhenExpressComponentsAreAvailable(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout, true);

        $this->assertContains(self::HANDLE, $layout->getUpdate()->getHandles());
    }

    public function testDoesNotAddTheLayoutHandleWhenExpressComponentsAreUnavailable(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout, false);

        $this->assertNotContains(self::HANDLE, $layout->getUpdate()->getHandles());
    }

    private function executeObserver(LayoutInterface $layout, bool $isAvailable): void
    {
        $availability = $this->createStub(ExpressComponentsAvailability::class);
        $availability->method('isAvailable')->willReturn($isAvailable);

        $event = $this->objectManager->create(Event::class);
        $event->setData('layout', $layout);

        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        $instance = $this->objectManager->create(AddExpressComponentsLayoutHandle::class, [
            'availability' => $availability,
        ]);

        $instance->execute($observer);
    }
}
