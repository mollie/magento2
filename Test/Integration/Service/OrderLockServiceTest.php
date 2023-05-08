<?php

namespace Mollie\Payment\Test\Integration\Service;

use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderLockServiceTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testKeepsTheTransactionId(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId('test_value');

        /** @var OrderLockService $instance */
        $instance = $this->objectManager->create(OrderLockService::class);

        $instance->execute($order, function ($order) {
            $this->assertEquals('test_value', $order->getMollieTransactionId());
        });
    }
}
