<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\LinkTransactionToOrder;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LinkTransactionToOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotCreateASecondRowForTheSameTransactionAndOrder(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var LinkTransactionToOrder $instance */
        $instance = $this->objectManager->create(LinkTransactionToOrder::class);

        $instance->execute('tr_duplicate123', $order);
        $instance->execute('tr_duplicate123', $order);

        $this->assertSame(1, $this->countRows('tr_duplicate123', (int)$order->getEntityId()));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testStillLinksTheTransactionToTheOrder(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var LinkTransactionToOrder $instance */
        $instance = $this->objectManager->create(LinkTransactionToOrder::class);
        $instance->execute('tr_single123', $order);

        $this->assertSame(1, $this->countRows('tr_single123', (int)$order->getEntityId()));
        $this->assertSame('tr_single123', $order->getMollieTransactionId());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testKeepsTheSkippedFlagWhenTheSameTransactionIsLinkedAgain(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var LinkTransactionToOrder $instance */
        $instance = $this->objectManager->create(LinkTransactionToOrder::class);
        $instance->execute('tr_skipped123', $order);

        $this->objectManager->create(ExpiredOrderToTransaction::class)->markTransactionAsSkipped('tr_skipped123');

        $instance->execute('tr_skipped123', $order);

        $transaction = $this->objectManager->create(ExpiredOrderToTransaction::class)
            ->getByTransactionId('tr_skipped123');

        $this->assertSame(1, $this->countRows('tr_skipped123', (int)$order->getEntityId()));
        $this->assertSame(1, (int)$transaction->getSkipped());
    }

    private function countRows(string $transactionId, int $orderId): int
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter('transaction_id', $transactionId);
        $searchCriteriaBuilder->addFilter('order_id', $orderId);

        return $this->objectManager->get(TransactionToOrderRepositoryInterface::class)
            ->getList($searchCriteriaBuilder->create())
            ->getTotalCount();
    }
}
