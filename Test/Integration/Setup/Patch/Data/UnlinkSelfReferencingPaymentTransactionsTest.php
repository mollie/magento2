<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Setup\Patch\Data\UnlinkSelfReferencingPaymentTransactions;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class UnlinkSelfReferencingPaymentTransactionsTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItRemovesTheParentOfAMollieTransactionThatIsItsOwnParent(): void
    {
        $order = $this->orderWithPaymentMethod(Creditcard::CODE);
        $transactionId = $this->insertTransaction($order, 'tr_self');
        $this->setParent($transactionId, $transactionId);

        $this->applyPatch();

        $this->assertNull($this->readParent($transactionId));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItKeepsAValidParentOfAMollieTransaction(): void
    {
        $order = $this->orderWithPaymentMethod(Creditcard::CODE);
        $parentId = $this->insertTransaction($order, 'tr_parent');
        $childId = $this->insertTransaction($order, 'tr_parent-capture');
        $this->setParent($childId, $parentId);

        $this->applyPatch();

        $this->assertSame($parentId, $this->readParent($childId));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testItLeavesTransactionsOfOtherPaymentMethodsUntouched(): void
    {
        $order = $this->orderWithPaymentMethod('checkmo');
        $transactionId = $this->insertTransaction($order, 'other_self');
        $this->setParent($transactionId, $transactionId);

        $this->applyPatch();

        $this->assertSame($transactionId, $this->readParent($transactionId));
    }

    private function orderWithPaymentMethod(string $paymentMethod): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod($paymentMethod);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $order;
    }

    private function insertTransaction(OrderInterface $order, string $txnId): int
    {
        $connection = $this->connection();
        $connection->insert($connection->getTableName('sales_payment_transaction'), [
            'order_id' => $order->getEntityId(),
            'payment_id' => $order->getPayment()->getEntityId(),
            'txn_id' => $txnId,
            'txn_type' => 'authorization',
            'is_closed' => 0,
        ]);

        return (int)$connection->lastInsertId();
    }

    private function setParent(int $transactionId, int $parentId): void
    {
        $connection = $this->connection();
        $connection->update(
            $connection->getTableName('sales_payment_transaction'),
            ['parent_id' => $parentId],
            ['transaction_id = ?' => $transactionId],
        );
    }

    private function readParent(int $transactionId): ?int
    {
        $connection = $this->connection();
        $select = $connection->select()
            ->from($connection->getTableName('sales_payment_transaction'), ['parent_id'])
            ->where('transaction_id = ?', $transactionId);

        $parentId = $connection->fetchOne($select);

        return $parentId === null ? null : (int)$parentId;
    }

    private function applyPatch(): void
    {
        $this->objectManager->create(UnlinkSelfReferencingPaymentTransactions::class)->apply();
    }

    private function connection(): AdapterInterface
    {
        return $this->objectManager->get(ResourceConnection::class)->getConnection();
    }
}
