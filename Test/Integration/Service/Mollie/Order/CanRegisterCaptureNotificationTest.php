<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\Order\CanRegisterCaptureNotification;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class CanRegisterCaptureNotificationTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 0
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testCanCaptureWhenDisabled(): void
    {
        $order = $this->loadOrderById('100000001');

        /** @var CanRegisterCaptureNotification $instance */
        $instance = $this->objectManager->create(CanRegisterCaptureNotification::class);

        $molliePayment = new Payment(new MollieApiClient());
        $molliePayment->status = 'paid';
        $molliePayment->amountCaptured = new stdClass();
        $molliePayment->amountCaptured->value = -999;

        $this->assertTrue($instance->execute($order, $molliePayment));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 0
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testCanCaptureWhenEnabledButNotCreditcard(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_ideal');

        /** @var CanRegisterCaptureNotification $instance */
        $instance = $this->objectManager->create(CanRegisterCaptureNotification::class);

        $molliePayment = new Payment(new MollieApiClient());
        $molliePayment->status = 'paid';
        $molliePayment->amountCaptured = new stdClass();
        $molliePayment->amountCaptured->value = -999;

        $this->assertTrue($instance->execute($order, $molliePayment));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_applepay/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testCannotCaptureWhenEnabledAndCreditcard(): void
    {
        $order = $this->loadOrderById('100000001');
        $methods = ['mollie_methods_applepay', 'mollie_methods_creditcard'];
        $order->getPayment()->setMethod($methods[array_rand($methods)]);

        /** @var CanRegisterCaptureNotification $instance */
        $instance = $this->objectManager->create(CanRegisterCaptureNotification::class);

        $molliePayment = new Payment(new MollieApiClient());
        $molliePayment->status = 'paid';
        $molliePayment->amountCaptured = new stdClass();
        $molliePayment->amountCaptured->value = 0;

        $this->assertFalse($instance->execute($order, $molliePayment));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCanRegisterWhenManualCaptureAndTheOnlyOrderOfTheTransactionIsCaptured(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->setMollieTransactionId('tr_singleorder');

        $instance = $this->objectManager->create(CanRegisterCaptureNotification::class);

        $this->assertTrue($instance->execute($order, $this->createCapturedPayment(100.0)));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testCannotRegisterWhenManualCaptureAndTheTransactionIsSharedWithAnotherOrder(): void
    {
        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        $orders = [];
        foreach (['100000002', '100000004'] as $incrementId) {
            $order = $this->loadOrderById($incrementId);
            $order->getPayment()->setMethod('mollie_methods_creditcard');
            $order->setMollieTransactionId('tr_sharedtransaction');
            $orders[] = $repository->save($order);
        }

        $instance = $this->objectManager->create(CanRegisterCaptureNotification::class);

        $this->assertFalse($instance->execute($orders[1], $this->createCapturedPayment(100.0)));
    }

    private function createCapturedPayment(float $amountCaptured): Payment
    {
        $molliePayment = new Payment(new MollieApiClient());
        $molliePayment->status = 'paid';
        $molliePayment->amountCaptured = new stdClass();
        $molliePayment->amountCaptured->value = $amountCaptured;

        return $molliePayment;
    }
}
