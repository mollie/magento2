<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\Order\SupportsPartialCapture;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SupportsPartialCaptureTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsTrueForAMethodThatSupportsPartialCaptures(): void
    {
        $order = $this->prepareOrder('mollie_methods_creditcard', 'tr_dummytransaction');

        $instance = $this->objectManager->create(SupportsPartialCapture::class);

        $this->assertTrue($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsFalseForRiverty(): void
    {
        $order = $this->prepareOrder('mollie_methods_riverty', 'tr_dummytransaction');

        $instance = $this->objectManager->create(SupportsPartialCapture::class);

        $this->assertFalse($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsFalseForBillink(): void
    {
        $order = $this->prepareOrder('mollie_methods_billink', 'tr_dummytransaction');

        $instance = $this->objectManager->create(SupportsPartialCapture::class);

        $this->assertFalse($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testReturnsTrueForALegacyOrdersApiTransactionThatCanShipSeparateLines(): void
    {
        $order = $this->prepareOrder('mollie_methods_riverty', 'ord_dummytransaction');

        $instance = $this->objectManager->create(SupportsPartialCapture::class);

        $this->assertTrue($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_riverty/supports_partial_capture 1
     */
    public function testFollowsTheStoreConfiguration(): void
    {
        $order = $this->prepareOrder('mollie_methods_riverty', 'tr_dummytransaction');

        $instance = $this->objectManager->create(SupportsPartialCapture::class);

        $this->assertTrue($instance->execute($order));
    }

    private function prepareOrder(string $method, string $transactionId): OrderInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId($transactionId);

        return $order;
    }
}
