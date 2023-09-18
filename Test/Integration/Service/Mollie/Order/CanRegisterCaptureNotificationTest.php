<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\Order\CanRegisterCaptureNotification;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

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
        $molliePayment->amountCaptured = new \stdClass();
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
        $molliePayment->amountCaptured = new \stdClass();
        $molliePayment->amountCaptured->value = -999;

        $this->assertTrue($instance->execute($order, $molliePayment));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 1
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testCannotCaptureWhenEnabledAndCreditcard(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');

        /** @var CanRegisterCaptureNotification $instance */
        $instance = $this->objectManager->create(CanRegisterCaptureNotification::class);

        $molliePayment = new Payment(new MollieApiClient());
        $molliePayment->status = 'paid';
        $molliePayment->amountCaptured = new \stdClass();
        $molliePayment->amountCaptured->value = -999;

        $this->assertFalse($instance->execute($order, $molliePayment));
    }
}
