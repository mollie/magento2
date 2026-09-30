<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Cron;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Cron\CancelExpiredPaymentLinkOrders;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Test\Fakes\Service\Mollie\ProcessTransactionFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use RuntimeException;

class CancelExpiredPaymentLinkOrdersTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCancelsAnExpiredPaymentLinkOrderWithoutAPayment(): void
    {
        $order = $this->savePaymentLinkOrder('P29D', null);

        $this->objectManager->create(CancelExpiredPaymentLinkOrders::class)->execute();

        $this->assertEquals(Order::STATE_CANCELED, $this->reload($order)->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testProcessesThePaymentOfAnExpiredPaymentLinkOrder(): void
    {
        $processTransaction = $this->loadProcessTransactionFake();
        $order = $this->savePaymentLinkOrder('P29D', 'tr_expired');

        $this->objectManager->create(CancelExpiredPaymentLinkOrders::class)->execute();

        $this->assertSame(1, $processTransaction->getTimesCalled());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $this->reload($order)->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLeavesAPaymentLinkOrderOpenWhileTheLinkIsValid(): void
    {
        $processTransaction = $this->loadProcessTransactionFake();
        $order = $this->savePaymentLinkOrder('P1D', 'tr_open');

        $this->objectManager->create(CancelExpiredPaymentLinkOrders::class)->execute();

        $this->assertSame(0, $processTransaction->getTimesCalled());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $this->reload($order)->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLeavesAPaymentLinkOrderOpenWhenTheLinkExpiredMoreThanAWeekAgo(): void
    {
        $order = $this->savePaymentLinkOrder('P60D', null);

        $this->objectManager->create(CancelExpiredPaymentLinkOrders::class)->execute();

        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $this->reload($order)->getState());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testContinuesWithTheNextOrderWhenAnOrderFails(): void
    {
        $processTransaction = $this->loadProcessTransactionFake();
        $processTransaction->setException(new RuntimeException('Mollie API is unavailable'));
        $failingOrder = $this->savePaymentLinkOrder('P29D', 'tr_failing');
        $nextOrder = $this->savePaymentLinkOrder('P30D', null, '100000002');

        $this->objectManager->create(CancelExpiredPaymentLinkOrders::class)->execute();

        $this->assertSame(1, $processTransaction->getTimesCalled());
        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $this->reload($failingOrder)->getState());
        $this->assertEquals(Order::STATE_CANCELED, $this->reload($nextOrder)->getState());
    }

    private function loadProcessTransactionFake(): ProcessTransactionFake
    {
        $fake = $this->objectManager->create(ProcessTransactionFake::class);
        $fake->setResponse($this->objectManager->create(GetMollieStatusResult::class, ['status' => 'expired']));
        $this->objectManager->addSharedInstance($fake, ProcessTransaction::class);

        return $fake;
    }

    private function savePaymentLinkOrder(
        string $createdAgo,
        ?string $transactionId,
        string $incrementId = '100000001',
    ): OrderInterface {
        $order = $this->loadOrderById($incrementId);
        $order->setStoreId(1);
        $order->getPayment()->setMethod(Paymentlink::CODE);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setMollieTransactionId($transactionId);
        $order->setCreatedAt(
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->sub(new DateInterval($createdAgo))
                ->format('Y-m-d H:i:s'),
        );

        return $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
    }

    private function reload(OrderInterface $order): OrderInterface
    {
        return $this->objectManager->create(OrderRepositoryInterface::class)->get($order->getEntityId());
    }
}
