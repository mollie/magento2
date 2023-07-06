<?php

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
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
    public function testDoesNothingWhenTheApiMethodIsOrders(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureMode $instance */
        $instance = $this->objectManager->create(CaptureMode::class);

        $transaction = $instance->process($order, Orders::CHECKOUT_TYPE, []);

        $this->assertArrayNotHasKey('captureMode', $transaction);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNothingWhenThePaymentMethodIsNotCreditcard(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);

        /** @var CaptureMode $instance */
        $instance = $this->objectManager->create(CaptureMode::class);

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

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

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

        $this->assertArrayNotHasKey('captureMode', $transaction);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testSetsTheModeWhenApplicable(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Creditcard::CODE);

        /** @var CaptureMode $instance */
        $instance = $this->objectManager->create(CaptureMode::class);

        $transaction = $instance->process($order, Payments::CHECKOUT_TYPE, []);

        $this->assertArrayHasKey('captureMode', $transaction);
    }
}
