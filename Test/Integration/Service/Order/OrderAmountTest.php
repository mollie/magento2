<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Service\Order\OrderAmount;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderAmountTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testCalculatesTheRightsAmount()
    {
        $transactionId = 'test_transaction_id';

        $orders = [];
        $orders[] = $this->loadOrderById('100000001');
        $orders[] = $this->loadOrderById('100000002');
        $orders[] = $this->loadOrderById('100000003');
        $orders[] = $this->loadOrderById('100000004');

        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        foreach ($orders as $order) {
            $order->setMollieTransactionId($transactionId);
            $order->setBaseCurrencyCode('USD');
            $order->setOrderCurrencyCode('USD');
            $repository->save($order);
        }

        /** @var OrderAmount $instance */
        $instance = $this->objectManager->create(OrderAmount::class);
        $result = $instance->getByTransactionId($transactionId);

        // 100 + 120 + 140 + 140 = 500
        $this->assertEquals(500, $result['value']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testThrowsExceptionWhenMixingCurrencies()
    {
        $transactionId = 'test_transaction_id';

        $orders = [];
        $orders[] = $this->loadOrderById('100000001');
        $orders[] = $this->loadOrderById('100000002');
        $orders[] = $this->loadOrderById('100000003');
        $orders[] = $this->loadOrderById('100000004');

        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        foreach ($orders as $i => $order) {
            $order->setMollieTransactionId($transactionId);
            $order->setBaseCurrencyCode($i % 2 == 0 ? 'EUR' : 'USD');
            $order->setOrderCurrencyCode($i % 2 == 0 ? 'EUR' : 'USD');
            $repository->save($order);
        }

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The orders have different currencies (EUR, USD)');

        /** @var OrderAmount $instance */
        $instance = $this->objectManager->create(OrderAmount::class);
        $instance->getByTransactionId($transactionId);
    }
}
