<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Order\ExpiredPaymentLinkOrders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ExpiredPaymentLinkOrdersTest extends IntegrationTestCase
{
    /**
     * @dataProvider openStateProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    #[DataProvider('openStateProvider')]
    public function testReturnsAnOpenPaymentLinkOrderWhenTheLinkIsExpired(string $state): void
    {
        $order = $this->saveOrder('100000001', Paymentlink::CODE, $state, 'P29D');

        $this->assertContains($order->getEntityId(), $this->getExpiredOrderIds($order));
    }

    public static function openStateProvider(): array
    {
        return [
            'new' => [Order::STATE_NEW],
            'pending payment' => [Order::STATE_PENDING_PAYMENT],
        ];
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 5
     */
    public function testUsesTheConfiguredDaysOfTheStore(): void
    {
        $order = $this->saveOrder('100000001', Paymentlink::CODE, Order::STATE_PENDING_PAYMENT, 'P6D');

        $this->assertContains($order->getEntityId(), $this->getExpiredOrderIds($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSkipsAPaymentLinkOrderWhenTheLinkIsValid(): void
    {
        $order = $this->saveOrder('100000001', Paymentlink::CODE, Order::STATE_PENDING_PAYMENT, 'P27D');

        $this->assertNotContains($order->getEntityId(), $this->getExpiredOrderIds($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSkipsAPaymentLinkOrderWhoseLinkExpiredMoreThanAWeekAgo(): void
    {
        $order = $this->saveOrder('100000001', Paymentlink::CODE, Order::STATE_PENDING_PAYMENT, 'P36D');

        $this->assertNotContains($order->getEntityId(), $this->getExpiredOrderIds($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSkipsOrdersOfOtherPaymentMethods(): void
    {
        $order = $this->saveOrder('100000001', 'mollie_methods_ideal', Order::STATE_PENDING_PAYMENT, 'P29D');

        $this->assertNotContains($order->getEntityId(), $this->getExpiredOrderIds($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSkipsPaymentLinkOrdersThatAreNotOpen(): void
    {
        $order = $this->saveOrder('100000001', Paymentlink::CODE, Order::STATE_PROCESSING, 'P29D');

        $this->assertNotContains($order->getEntityId(), $this->getExpiredOrderIds($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testReturnsTheMostRecentlyExpiredOrderFirst(): void
    {
        $olderOrder = $this->saveOrder('100000002', Paymentlink::CODE, Order::STATE_PENDING_PAYMENT, 'P33D');
        $newerOrder = $this->saveOrder('100000001', Paymentlink::CODE, Order::STATE_PENDING_PAYMENT, 'P29D');

        $result = $this->getExpiredOrderIds($newerOrder);

        $this->assertSame([(string) $newerOrder->getEntityId(), (string) $olderOrder->getEntityId()], $result);
    }

    private function saveOrder(string $incrementId, string $method, string $state, string $createdAgo): OrderInterface
    {
        $order = $this->loadOrderById($incrementId);
        $order->getPayment()->setMethod($method);
        $order->setState($state);
        $order->setStoreId(1);
        $order->setCreatedAt(
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->sub(new DateInterval($createdAgo))
                ->format('Y-m-d H:i:s'),
        );

        return $this->objectManager->get(OrderRepositoryInterface::class)->save($order);
    }

    /**
     * @return string[]
     */
    private function getExpiredOrderIds(OrderInterface $order): array
    {
        return array_map(
            static fn (OrderInterface $expiredOrder): string => (string) $expiredOrder->getEntityId(),
            $this->objectManager->create(ExpiredPaymentLinkOrders::class)->getForStore((int) $order->getStoreId()),
        );
    }
}
