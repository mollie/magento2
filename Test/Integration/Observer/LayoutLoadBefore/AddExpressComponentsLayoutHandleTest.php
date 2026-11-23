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
    private const BETA_PRODUCT_HANDLE = 'mollie_express_components_beta_product';
    private const BETA_SHIPPING_HANDLE = 'mollie_express_components_beta_shipping';
    private const BETA_MINICART_HANDLE = 'mollie_express_components_beta_minicart';
    private const MINICART_DISABLED_HANDLE = 'mollie_express_components_minicart_disabled';

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

    /**
     * The shipping step and the product page need the shipping options beta of Mollie. All placements default to on.
     *
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testAddsAllBetaPlacementHandlesWhenTheBetaIsEnabled(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $handles = $layout->getUpdate()->getHandles();
        $this->assertContains(self::BETA_PRODUCT_HANDLE, $handles);
        $this->assertContains(self::BETA_SHIPPING_HANDLE, $handles);
        $this->assertContains(self::BETA_MINICART_HANDLE, $handles);
        $this->assertNotContains(self::MINICART_DISABLED_HANDLE, $handles);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 0
     */
    public function testDoesNotAddTheBetaPlacementHandlesWhenTheBetaIsDisabled(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $handles = $layout->getUpdate()->getHandles();
        $this->assertContains(self::HANDLE, $handles);
        $this->assertNotContains(self::BETA_PRODUCT_HANDLE, $handles);
        $this->assertNotContains(self::BETA_SHIPPING_HANDLE, $handles);
        $this->assertNotContains(self::BETA_MINICART_HANDLE, $handles);
        $this->assertNotContains(self::MINICART_DISABLED_HANDLE, $handles);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 0
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     */
    public function testDoesNotAddTheBetaPlacementHandlesWhenExpressComponentsAreDisabled(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $handles = $layout->getUpdate()->getHandles();
        $this->assertNotContains(self::BETA_PRODUCT_HANDLE, $handles);
        $this->assertNotContains(self::BETA_SHIPPING_HANDLE, $handles);
        $this->assertNotContains(self::BETA_MINICART_HANDLE, $handles);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/show_product_page 0
     */
    public function testSkipsTheProductPageHandleWhenTheProductPagePlacementIsOff(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $handles = $layout->getUpdate()->getHandles();
        $this->assertNotContains(self::BETA_PRODUCT_HANDLE, $handles);
        $this->assertContains(self::BETA_SHIPPING_HANDLE, $handles);
        $this->assertContains(self::BETA_MINICART_HANDLE, $handles);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/show_shipping_step 0
     */
    public function testSkipsTheShippingStepHandleWhenTheShippingStepPlacementIsOff(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $handles = $layout->getUpdate()->getHandles();
        $this->assertNotContains(self::BETA_SHIPPING_HANDLE, $handles);
        $this->assertContains(self::BETA_PRODUCT_HANDLE, $handles);
        $this->assertContains(self::BETA_MINICART_HANDLE, $handles);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/beta_shipping_options 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/show_minicart 0
     */
    public function testDisablesTheMinicartWhenTheMinicartPlacementIsOff(): void
    {
        $layout = $this->objectManager->create(LayoutInterface::class);

        $this->executeObserver($layout);

        $handles = $layout->getUpdate()->getHandles();
        $this->assertNotContains(self::BETA_MINICART_HANDLE, $handles);
        $this->assertContains(self::MINICART_DISABLED_HANDLE, $handles);
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
