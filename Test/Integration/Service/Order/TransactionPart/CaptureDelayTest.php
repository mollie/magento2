<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Service\Order\TransactionPart\CaptureDelay;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CaptureDelayTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenCaptureModeIsManual(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureDelay $instance */
        $instance = $this->objectManager->create(CaptureDelay::class);

        $transaction = $instance->process($order, []);

        $this->assertArrayNotHasKey('captureDelay', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenCaptureDelayIsNotConfigured(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureDelay $instance */
        $instance = $this->objectManager->create(CaptureDelay::class);

        $transaction = $instance->process($order, []);

        $this->assertArrayNotHasKey('captureDelay', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_delay 2
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_delay_unit hours
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTheCaptureDelayInHours(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureDelay $instance */
        $instance = $this->objectManager->create(CaptureDelay::class);

        $transaction = $instance->process($order, []);

        $this->assertSame('2 hours', $transaction['captureDelay']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_delay 3
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_delay_unit days
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTheCaptureDelayInDays(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureDelay $instance */
        $instance = $this->objectManager->create(CaptureDelay::class);

        $transaction = $instance->process($order, []);

        $this->assertSame('3 days', $transaction['captureDelay']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_delay abc
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenCaptureDelayIsNotNumeric(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureDelay $instance */
        $instance = $this->objectManager->create(CaptureDelay::class);

        $transaction = $instance->process($order, []);

        $this->assertArrayNotHasKey('captureDelay', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_delay 4
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesHoursAsDefaultUnitWhenNotOverridden(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureDelay $instance */
        $instance = $this->objectManager->create(CaptureDelay::class);

        $transaction = $instance->process($order, []);

        $this->assertSame('4 hours', $transaction['captureDelay']);
    }
}
