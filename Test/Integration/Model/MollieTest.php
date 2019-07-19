<?php

namespace Mollie\Payment\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Test\Integration\TestCase;

class MollieTest extends TestCase
{
    public function processTransactionUsesTheCorrectApiProvider()
    {
        return [
            'orders' => ['ord_abcdefg', 'orders'],
            'payments' => ['tr_abcdefg', 'payments'],
        ];
    }

    /**
     * @dataProvider processTransactionUsesTheCorrectApiProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testProcessTransactionUsesTheCorrectApi($orderId, $type)
    {
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($orderId);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $ordersApiMock = $this->createMock(Orders::class);
        $paymentsApiMock = $this->createMock(Payments::class);

        if ($type == 'orders') {
            $ordersApiMock->expects($this->once())->method('processTransaction');
        }

        if ($type == 'payments') {
            $paymentsApiMock->expects($this->once())->method('processTransaction');
        }

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
        ]);

        $instance->processTransaction($order->getEntityId());
    }
}
