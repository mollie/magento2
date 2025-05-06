<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Mollie\Payment\Service\Order\TransactionPart\LimitedMethods;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LimitedMethodsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTheLimitedMethodsWhenValid(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation('limited_methods', ['ideal', 'bancontact']);

        /** @var LimitedMethods $instance */
        $instance = $this->objectManager->create(LimitedMethods::class);

        $result = $instance->process($order, 'orders', ['method' => 'creditcard']);

        $this->assertArrayHasKey('method', $result);
        $this->assertEquals(['ideal', 'bancontact'], $result['method']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenTheValueIsEmpty(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setAdditionalInformation('limited_methods', rand(0, 1) == 1 ? null : '');

        /** @var LimitedMethods $instance */
        $instance = $this->objectManager->create(LimitedMethods::class);

        $result = $instance->process($order, 'orders', ['method' => 'creditcard']);

        $this->assertEquals('creditcard', $result['method']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenNotSet(): void
    {
        $order = $this->loadOrderById('100000001');

        /** @var LimitedMethods $instance */
        $instance = $this->objectManager->create(LimitedMethods::class);

        $result = $instance->process($order, 'orders', ['method' => 'creditcard']);

        $this->assertEquals('creditcard', $result['method']);
    }
}
