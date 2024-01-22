<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Service\Order\MethodCode;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoDataFixture Magento/Sales/_files/order.php
 */
class MethodCodeTest extends IntegrationTestCase
{
    public function testGetsTheCorrectCode(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_ideal');

        $instance = $this->objectManager->create(MethodCode::class);

        $result = $instance->execute($order);

        $this->assertEquals('ideal', $result);
    }

    public function testReturnsNothingWhenItsNotAMollieMethod(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('wrong_method');

        $instance = $this->objectManager->create(MethodCode::class);

        $result = $instance->execute($order);

        $this->assertEquals('', $result);
    }

    public function testReturnsNothingWhenPaymentLinkHasMultipleMethods(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');
        $order->getPayment()->setAdditionalInformation(
            'limited_methods',
            ['mollie_methods_ideal', 'mollie_methods_eps']
        );

        $instance = $this->objectManager->create(MethodCode::class);

        $result = $instance->execute($order);

        $this->assertEquals('', $result);
    }

    public function testReturnsPaymentLinkReturnsTheSingleLimitedMethod(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');
        $order->getPayment()->setAdditionalInformation(
            'limited_methods',
            ['mollie_methods_ideal']
        );

        $instance = $this->objectManager->create(MethodCode::class);

        $result = $instance->execute($order);

        $this->assertEquals('ideal', $result);
    }

    public function testReturnsNothingWhenLimitedMethodsIsNull(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');
        $order->getPayment()->setAdditionalInformation(
            'limited_methods',
            null
        );

        $instance = $this->objectManager->create(MethodCode::class);

        $result = $instance->execute($order);

        $this->assertEquals('', $result);
    }

    public function testReturnsMethodAsExpiryMethod(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_ideal');

        $instance = $this->objectManager->create(MethodCode::class);

        $instance->execute($order);

        $this->assertEquals('ideal', $instance->getExpiresAtMethod());
    }

    public function testReturnsPaymentLinkAsExpiryMethodWhenApplicable(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');
        $order->getPayment()->setAdditionalInformation(
            'limited_methods',
            ['mollie_methods_ideal', 'mollie_methods_eps']
        );

        $instance = $this->objectManager->create(MethodCode::class);

        $instance->execute($order);

        $this->assertEquals('paymentlink', $instance->getExpiresAtMethod());
    }

    public function testReturnsMethodWhenSingleLimitedMethod(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_paymentlink');
        $order->getPayment()->setAdditionalInformation(
            'limited_methods',
            ['mollie_methods_ideal']
        );

        $instance = $this->objectManager->create(MethodCode::class);

        $instance->execute($order);

        $this->assertEquals('ideal', $instance->getExpiresAtMethod());
    }
}
