<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Block\Adminhtml\Sales\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Mollie\Payment\Block\Adminhtml\Sales\Order\PartialCaptureNotSupportedNotice;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @magentoAppArea adminhtml
 */
class PartialCaptureNotSupportedNoticeTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/when_to_capture shipment
     */
    public function testShowsTheNoticeForAMethodWithoutPartialCaptures(): void
    {
        $html = $this->render('mollie_methods_riverty');

        $this->assertStringContainsString('Riverty does not support partial captures', $html);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture shipment
     */
    public function testHidesTheNoticeForAMethodWithPartialCaptures(): void
    {
        $this->assertSame('', $this->render('mollie_methods_creditcard'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/capture_mode automatic
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/when_to_capture shipment
     */
    public function testHidesTheNoticeWhenTheOrderIsCapturedAutomatically(): void
    {
        $this->assertSame('', $this->render('mollie_methods_riverty'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/when_to_capture invoice
     */
    public function testHidesTheNoticeOnThePageThatDoesNotTriggerTheCapture(): void
    {
        $this->assertSame('', $this->render('mollie_methods_riverty'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/capture_mode manual
     */
    public function testHidesTheNoticeWhenNoCaptureMomentIsConfigured(): void
    {
        $this->assertSame('', $this->render('mollie_methods_riverty'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testHidesTheNoticeForANonMollieOrder(): void
    {
        $this->assertSame('', $this->render('checkmo'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testHidesTheNoticeWhenNothingIsRegistered(): void
    {
        $block = $this->objectManager->create(PartialCaptureNotSupportedNotice::class);
        $block->setData('registry_key', 'current_shipment');
        $block->setData('capture_moment', 'shipment');

        $this->assertSame('', $block->toHtml());
    }

    /**
     * @dataProvider layoutHandlesProvider
     */
    #[DataProvider('layoutHandlesProvider')]
    public function testIsAddedToTheLayoutHandleWithTheMatchingArguments(
        string $handle,
        string $registryKey,
        string $captureMoment,
    ): void {
        $layout = $this->objectManager->create(LayoutInterface::class);
        $layout->getUpdate()->load($handle);

        $blocks = $layout->getUpdate()->asSimplexml()
            ->xpath('//block[@name="mollie.partial.capture.not.supported"]');

        $this->assertNotEmpty($blocks, sprintf('The notice is not added to the "%s" layout handle', $handle));

        $arguments = $this->argumentsOf($blocks[0]);

        $this->assertSame($registryKey, $arguments['registry_key'] ?? null);
        $this->assertSame($captureMoment, $arguments['capture_moment'] ?? null);
    }

    public static function layoutHandlesProvider(): array
    {
        return [
            ['sales_order_invoice_new', 'current_invoice', 'invoice'],
            ['sales_order_invoice_updateqty', 'current_invoice', 'invoice'],
            ['adminhtml_order_shipment_new', 'current_shipment', 'shipment'],
        ];
    }

    private function argumentsOf(\SimpleXMLElement $block): array
    {
        $arguments = [];
        foreach ($block->xpath('arguments/argument') as $argument) {
            $arguments[(string) $argument->attributes()->name] = (string) $argument;
        }

        return $arguments;
    }

    private function render(string $method): string
    {
        /** @var Order $order */
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId('tr_dummytransaction');

        /** @var Shipment $shipment */
        $shipment = $this->objectManager->create(Shipment::class);
        $shipment->setOrder($order);

        $this->objectManager->get(Registry::class)->unregister('current_shipment');
        $this->objectManager->get(Registry::class)->register('current_shipment', $shipment);

        $block = $this->objectManager->create(PartialCaptureNotSupportedNotice::class);
        $block->setData('registry_key', 'current_shipment');
        $block->setData('capture_moment', 'shipment');
        $block->setTemplate('Mollie_Payment::order/partial_capture_not_supported.phtml');

        return $block->toHtml();
    }
}
