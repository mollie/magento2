<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\OrderReachedSuccessPage;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderReachedSuccessPageTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testReturnsTrueWhenTheOrderWasRedirectedToTheSuccessPage(): void
    {
        $order = $this->loadOrder('100000001');
        $this->createTransactionToOrder((int) $order->getEntityId(), 1);

        $instance = $this->objectManager->create(OrderReachedSuccessPage::class);

        $this->assertTrue($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testReturnsFalseWhenTheOrderWasNotRedirectedToTheSuccessPage(): void
    {
        $order = $this->loadOrder('100000001');
        $this->createTransactionToOrder((int) $order->getEntityId(), 0);

        $instance = $this->objectManager->create(OrderReachedSuccessPage::class);

        $this->assertFalse($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testReturnsFalseWhenThereIsNoTransactionForTheOrder(): void
    {
        $order = $this->loadOrder('100000001');

        $instance = $this->objectManager->create(OrderReachedSuccessPage::class);

        $this->assertFalse($instance->execute($order));
    }

    private function createTransactionToOrder(int $orderId, int $redirected): void
    {
        $transactionToOrder = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder->setOrderId($orderId);
        $transactionToOrder->setTransactionId('tr_abc123');
        $transactionToOrder->setRedirected($redirected);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);
    }
}
