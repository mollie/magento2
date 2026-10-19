<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Mollie\Payment\Model\Client\Payments\Processors\SuccessfulPayment;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\Order\IsPaymentAlreadyProcessed;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;

class SuccessfulPaymentTest extends IntegrationTestCase
{
    private const TRANSACTION_ID = 'tr_TestTransaction';

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCanceledOrderGetsUncanceled(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');
        $order->setMollieTransactionId('abc123');

        $items = $order->getItems();
        foreach ($items as $item) {
            if (!$item->getSku()) {
                $item->setSku($item->getProduct()->getSku());
            }
        }

        $item = array_shift($items);
        $item->setCurrencyCode('EUR');

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
        $order->cancel();
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $this->assertEquals(Order::STATE_CANCELED, $order->getState());

        /** @var MolliePaymentBuilder $paymentBuilder */
        $paymentBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $paymentBuilder->setAmount((float)$order->getBaseGrandTotal(), $order->getBaseCurrencyCode());

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);
        $instance->process(
            $order,
            $paymentBuilder->build(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => $order->getIncrementId(),
                'type' => 'webhook',
            ]),
        );

        $freshOrder = $this->objectManager->get(OrderInterface::class)->load($order->getId(), 'entity_id');

        // There is a difference in ~2.3.4 and later, that's why we check both statuses as it is change somewhere in
        // those versions.
        $this->assertTrue(in_array(
            $freshOrder->getState(),
            [
                Order::STATE_PROCESSING,
                Order::STATE_COMPLETE,
            ],
        ), sprintf(
            'We expect the order status to be "processing" or "complete". Instead we got %s',
            $freshOrder->getState(),
        ));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testItMarksThePaymentAsProcessed(): void
    {
        $order = $this->prepareOrder();

        $this->processWebhook($order);

        $processedOrder = $this->loadOrder('100000001');

        $this->assertTrue(
            $processedOrder->getPayment()->getAdditionalInformation(IsPaymentAlreadyProcessed::PAYMENT_PROCESSED),
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testItIgnoresTheWebhookThatMollieSendsWhenTheRefundSettles(): void
    {
        $this->processWebhook($this->prepareOrder());

        $refundedOrder = $this->loadOrder('100000001');
        $refundedOrder->setState(Order::STATE_CLOSED);
        $refundedOrder->setStatus(Order::STATE_CLOSED);
        $this->removeTheStatusFlagThatOlderOrdersDoNotHave($refundedOrder);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($refundedOrder);

        $this->processWebhook($this->loadOrder('100000001'));

        $freshOrder = $this->loadOrder('100000001');

        $this->assertEquals(Order::STATE_CLOSED, $freshOrder->getState());
        $this->assertEquals(Order::STATE_CLOSED, $freshOrder->getStatus());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testItKeepsTheCaptureWhenManualCaptureIsEnabledAfterThePaymentWasCaptured(): void
    {
        $order = $this->prepareOrder(Creditcard::CODE);
        $order->getPayment()->setTransactionId(self::TRANSACTION_ID);
        $order->getPayment()->addTransaction(Transaction::TYPE_CAPTURE);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $this->processWebhook($this->loadOrder('100000001'), 'paid');

        $this->assertSame([Transaction::TYPE_CAPTURE => 1], $this->countTransactionsByType());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testItRegistersTheAuthorizationWhenThePaymentIsAuthorized(): void
    {
        $this->processWebhook($this->prepareOrder(Creditcard::CODE), 'authorized');

        $this->assertSame([Transaction::TYPE_AUTH => 1], $this->countTransactionsByType());
        $this->assertSame(0, $this->isAuthorizationClosed());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testItDoesNotReopenTheAuthorizationWhenThePaymentIsPaidLater(): void
    {
        $this->processWebhook($this->prepareOrder(Creditcard::CODE), 'authorized');
        $this->closeAuthorization();

        $this->processWebhook($this->loadOrder('100000001'), 'paid');

        $this->assertSame([Transaction::TYPE_AUTH => 1], $this->countTransactionsByType());
        $this->assertSame(1, $this->isAuthorizationClosed());
    }

    /**
     * @return array<string, int>
     */
    private function countTransactionsByType(): array
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('sales_payment_transaction'), ['txn_type', 'COUNT(*)'])
            ->where('order_id = ?', $this->loadOrder('100000001')->getEntityId())
            ->group('txn_type');

        return array_map('intval', $connection->fetchPairs($select));
    }

    private function isAuthorizationClosed(): int
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('sales_payment_transaction'), ['is_closed'])
            ->where('txn_id = ?', self::TRANSACTION_ID)
            ->where('txn_type = ?', Transaction::TYPE_AUTH);

        return (int)$connection->fetchOne($select);
    }

    private function closeAuthorization(): void
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $connection->update(
            $connection->getTableName('sales_payment_transaction'),
            ['is_closed' => 1],
            ['txn_id = ?' => self::TRANSACTION_ID],
        );
    }

    private function removeTheStatusFlagThatOlderOrdersDoNotHave(OrderInterface $order): void
    {
        $order->getPayment()->unsAdditionalInformation(IsPaymentAlreadyProcessed::STATUS_UPDATED);
    }

    private function prepareOrder(?string $paymentMethod = null): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId(self::TRANSACTION_ID);

        if ($paymentMethod !== null) {
            $order->getPayment()->setMethod($paymentMethod);
        }

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return $this->loadOrder('100000001');
    }

    private function processWebhook(OrderInterface $order, string $status = 'paid'): void
    {
        /** @var MolliePaymentBuilder $paymentBuilder */
        $paymentBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $paymentBuilder->setAmount((float)$order->getBaseGrandTotal(), $order->getBaseCurrencyCode());
        $paymentBuilder->setStatus($status);
        $paymentBuilder->setMethod('ideal');

        /** @var SuccessfulPayment $instance */
        $instance = $this->objectManager->create(SuccessfulPayment::class);
        $instance->process(
            $order,
            $paymentBuilder->build(),
            'webhook',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'paid',
                'order_id' => $order->getIncrementId(),
                'type' => 'webhook',
            ]),
        );
    }
}
