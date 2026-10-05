<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
    public function testCalculatesTheRightsAmount(): void
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
    public function testThrowsExceptionWhenMixingCurrencies(): void
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

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testThrowsExceptionWhenNoOrderHasTheTransactionId(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('No orders found for transaction tr_unknown');

        /** @var OrderAmount $instance */
        $instance = $this->objectManager->create(OrderAmount::class);
        $instance->getByTransactionId('tr_unknown');
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesTheOrderItselfWhenTheTransactionIsNotStoredOnAnyOrder(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId('tr_stored_on_order');
        $order->setBaseGrandTotal(133.32);
        $order->setGrandTotal(133.32);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
        $order->setMollieTransactionId('tr_from_webhook');

        /** @var OrderAmount $instance */
        $instance = $this->objectManager->create(OrderAmount::class);
        $result = $instance->forOrder($order);

        $this->assertEquals('133.32', $result['value']);
        $this->assertEquals('EUR', $result['currency']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testSumsAllOrdersWithTheSameTransactionIdForOrder(): void
    {
        $transactionId = 'test_transaction_id';

        $orders = [];
        $orders[] = $this->loadOrderById('100000001');
        $orders[] = $this->loadOrderById('100000002');

        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        foreach ($orders as $order) {
            $order->setMollieTransactionId($transactionId);
            $repository->save($order);
        }

        /** @var OrderAmount $instance */
        $instance = $this->objectManager->create(OrderAmount::class);
        $result = $instance->forOrder($orders[0]);

        // 100 + 120 = 220
        $this->assertEquals(220, $result['value']);
    }
}
