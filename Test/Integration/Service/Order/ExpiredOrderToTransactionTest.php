<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ExpiredOrderToTransactionTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testDoesNotIncludedSkippedTransactionsWhenCheckingForMultipleTransactions(): void
    {
        $transaction1 = uniqid();
        $transaction2 = uniqid();

        $order = $this->loadOrderById('100000001');

        /** @var TransactionToOrderInterface $transactionToOrder1 */
        $transactionToOrder1 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder1->setOrderId((int)$order->getEntityId());
        $transactionToOrder1->setTransactionId($transaction1);
        $transactionToOrder1->setSkipped(1);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder1);

        /** @var TransactionToOrderInterface $transactionToOrder2 */
        $transactionToOrder2 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder2->setOrderId((int)$order->getEntityId());
        $transactionToOrder2->setTransactionId($transaction2);
        $transactionToOrder1->setSkipped(0);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder2);

        /** @var ExpiredOrderToTransaction $instance */
        $instance = $this->objectManager->create(ExpiredOrderToTransaction::class);

        $this->assertFalse($instance->hasMultipleTransactions($order));
    }
}
