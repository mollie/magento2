<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Service\Order\TransactionPart\CaptureMode;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CaptureModeTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNothingWhenThePaymentMethodIsNotCreditCardOrApplePay(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);

        /** @var CaptureMode $instance */
        $instance = $this->objectManager->create(CaptureMode::class);

        $transaction = $instance->process($order, []);

        $this->assertArrayNotHasKey('captureMode', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 0
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testDoesNothingWhenNotEnabled(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureMode $instance */
        $instance = $this->objectManager->create(CaptureMode::class);

        $transaction = $instance->process($order, []);

        $this->assertArrayNotHasKey('captureMode', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_applepay/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testSetsTheModeWhenApplicable(): void
    {
        $order = $this->loadOrderById('100000001');
        $methods = [ApplePay::CODE, Creditcard::CODE];
        $order->getPayment()->setMethod($methods[array_rand($methods)]);

        /** @var CaptureMode $instance */
        $instance = $this->objectManager->create(CaptureMode::class);

        $transaction = $instance->process($order, []);

        $this->assertArrayHasKey('captureMode', $transaction);
    }
}
