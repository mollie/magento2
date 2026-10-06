<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Model\Client\Payments\Processors\ExpiredStatusProcessor;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;

class ExpiredStatusProcessorTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testCancelsWhenThereIsOnlyOneTransaction(): void
    {
        $transactionId = uniqid();

        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId($transactionId);

        /** @var TransactionToOrderInterface $transactionToOrder */
        $transactionToOrder = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder->setOrderId((int)$order->getEntityId());
        $transactionToOrder->setTransactionId($transactionId);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);

        /** @var ExpiredStatusProcessor $instance */
        $instance = $this->objectManager->get(ExpiredStatusProcessor::class);
        $instance->process(
            $order,
            $this->getMolliePayment(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ]),
        );

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testMarksTransactionAsSkippedWhenThereAreMultipleTransactions(): void
    {
        $transaction1 = uniqid();
        $transaction2 = uniqid();

        $order = $this->loadOrderById('100000001');
        $order->setMollieTransactionId($transaction1);

        /** @var TransactionToOrderInterface $transactionToOrder1 */
        $transactionToOrder1 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder1->setOrderId((int)$order->getEntityId());
        $transactionToOrder1->setTransactionId($transaction1);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder1);

        /** @var TransactionToOrderInterface $transactionToOrder2 */
        $transactionToOrder2 = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder2->setOrderId((int)$order->getEntityId());
        $transactionToOrder2->setTransactionId($transaction2);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder2);

        /** @var ExpiredStatusProcessor $instance */
        $instance = $this->objectManager->get(ExpiredStatusProcessor::class);
        $instance->process(
            $order,
            $this->getMolliePayment(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ]),
        );

        /** @var ExpiredOrderToTransaction $transactionToOrder */
        $transactionToOrder = $this->objectManager->get(ExpiredOrderToTransaction::class);
        $transaction = $transactionToOrder->getByTransactionId($transaction1);

        $this->assertEquals(1, $transaction->getSkipped());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testKeepsAPaymentLinkOrderOpenWhileTheLinkIsValid(): void
    {
        $transactionId = uniqid();
        $order = $this->createPaymentLinkOrderCreatedAgo('PT15M', $transactionId);

        $this->processExpiredPayment($order);

        $transaction = $this->objectManager->get(ExpiredOrderToTransaction::class)->getByTransactionId($transactionId);
        $this->assertNotEquals(Order::STATE_CANCELED, $order->getState());
        $this->assertEquals(1, $transaction->getSkipped());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsTheExpiryCommentOnlyOnceWhenTheSamePaymentIsProcessedTwice(): void
    {
        $transactionId = uniqid();
        $order = $this->createPaymentLinkOrderCreatedAgo('PT15M', $transactionId);

        $this->processExpiredPayment($order);
        $this->processExpiredPayment($order);

        $this->assertCount(1, $this->getCommentsContaining($order, $transactionId));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotAddTheExpiryCommentToAPaidPaymentLinkOrder(): void
    {
        $transactionId = uniqid();
        $order = $this->createPaymentLinkOrderCreatedAgo('PT15M', $transactionId);
        $order->setState(Order::STATE_PROCESSING);

        $this->processExpiredPayment($order);

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
        $this->assertCount(0, $this->getCommentsContaining($order, $transactionId));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCancelsAPaymentLinkOrderWhenTheLinkIsExpired(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P29D', uniqid());

        $this->processExpiredPayment($order);

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCancelsAPaymentLinkOrderWhenTheLinkExpiresAfterAnEarlierPaymentWasSkipped(): void
    {
        $transactionId = uniqid();
        $order = $this->createPaymentLinkOrderCreatedAgo('PT15M', $transactionId);
        $this->processExpiredPayment($order);

        $order->setCreatedAt($this->getDateAgo('P29D'));
        $this->processExpiredPayment($order);

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
    }

    private function createPaymentLinkOrderCreatedAgo(string $interval, string $transactionId): OrderInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Paymentlink::CODE);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setCreatedAt($this->getDateAgo($interval));
        $order->setMollieTransactionId($transactionId);

        /** @var TransactionToOrderInterface $transactionToOrder */
        $transactionToOrder = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder->setOrderId((int)$order->getEntityId());
        $transactionToOrder->setTransactionId($transactionId);
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);

        return $order;
    }

    private function getDateAgo(string $interval): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->sub(new DateInterval($interval))
            ->format('Y-m-d H:i:s');
    }

    private function processExpiredPayment(OrderInterface $order): void
    {
        $this->objectManager->create(ExpiredStatusProcessor::class)->process(
            $order,
            $this->getMolliePayment(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'test',
                'order_id' => '-01',
                'type' => 'webhook',
            ]),
        );
    }

    /**
     * @return string[]
     */
    private function getCommentsContaining(OrderInterface $order, string $text): array
    {
        $criteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('parent_id', $order->getEntityId())
            ->create();
        $history = $this->objectManager->get(OrderStatusHistoryRepositoryInterface::class)->getList($criteria);

        return array_values(array_filter(
            array_map(static fn ($item): string => (string) $item->getComment(), $history->getItems()),
            static fn (string $comment): bool => str_contains($comment, $text),
        ));
    }

    private function getMolliePayment(): Payment
    {
        /** @var MolliePaymentBuilder $orderBuilder */
        $orderBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $orderBuilder->setAmount(100, 'USD');
        $orderBuilder->setStatus('expired');

        return $orderBuilder->build();
    }
}
