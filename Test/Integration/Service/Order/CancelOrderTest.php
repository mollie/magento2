<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CancelOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRegisterCancellationCancelsTheOrder(): void
    {
        $order = $this->loadOrderById('100000001');

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $instance->execute($order);

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRegisterCancellationSetsTheCorrectMessage(): void
    {
        $order = $this->loadOrderById('100000001');

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $instance->execute($order, 'canceled');

        $payment = $order->getPayment();

        $this->assertEquals('The order was canceled, reason: payment canceled', $payment->getMessage()->render());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testDoesNotCancelAFullyInvoicedOrder(): void
    {
        $order = $this->givenAFullyInvoicedPendingPaymentOrder();

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $result = $instance->execute($order, 'expired');

        $this->assertFalse($result);
        $this->assertSame(Order::STATE_PENDING_PAYMENT, $this->loadOrderById('100000001')->getStatus());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testDoesNotAddCancelCommentsToAFullyInvoicedOrderOnRepeatedCalls(): void
    {
        $order = $this->givenAFullyInvoicedPendingPaymentOrder();

        /** @var CancelOrder $instance */
        $instance = $this->objectManager->create(CancelOrder::class);
        $instance->execute($order, 'expired');
        $instance->execute($this->loadOrderById('100000001'), 'expired');

        $this->assertSame([], $this->getCancelComments($order));
    }

    private function givenAFullyInvoicedPendingPaymentOrder(): OrderInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);

        return $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
    }

    /**
     * @return string[]
     */
    private function getCancelComments(OrderInterface $order): array
    {
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('parent_id', $order->getEntityId())
            ->create();

        $histories = $this->objectManager->get(OrderStatusHistoryRepositoryInterface::class)
            ->getList($searchCriteria)
            ->getItems();

        return array_values(array_filter(
            array_map(fn (OrderStatusHistoryInterface $history): string => (string) $history->getComment(), $histories),
            fn (string $comment): bool => str_starts_with($comment, 'The order was canceled'),
        ));
    }
}
