<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\LayoutLoadBefore;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Mollie\Payment\Observer\LayoutLoadBefore\AddExpressComponentsLayoutHandle;
use Mollie\Payment\Test\Fakes\Checkout\SessionFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AddExpressComponentsLayoutHandleTest extends IntegrationTestCase
{
    private const HANDLE = 'mollie_express_components';

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     */
    public function testAddsTheLayoutHandleWhenExpressComponentsAreEnabled(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $this->assertContains(self::HANDLE, $layout->getUpdate()->getHandles());
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 0
     */
    public function testDoesNotAddTheLayoutHandleWhenExpressComponentsAreDisabled(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $this->assertNotContains(self::HANDLE, $layout->getUpdate()->getHandles());
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     */
    public function testDoesNotLoadTheQuoteWhenExpressComponentsAreEnabled(): void
    {
        $session = $this->loadCheckoutSessionFake();

        $this->executeObserver($this->objectManager->create(LayoutInterface::class));

        $this->assertFalse($session->isQuoteRequested());
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 0
     */
    public function testDoesNotLoadTheQuoteWhenExpressComponentsAreDisabled(): void
    {
        $session = $this->loadCheckoutSessionFake();

        $this->executeObserver($this->objectManager->create(LayoutInterface::class));

        $this->assertFalse($session->isQuoteRequested());
    }

    private function loadCheckoutSessionFake(): SessionFake
    {
        $session = $this->objectManager->create(SessionFake::class);
        $this->objectManager->addSharedInstance($session, Session::class);

        return $session;
    }

    private function executeObserver(LayoutInterface $layout): void
    {
        $event = $this->objectManager->create(Event::class);
        $event->setData('layout', $layout);

        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        $this->objectManager->create(AddExpressComponentsLayoutHandle::class)->execute($observer);
    }
}
