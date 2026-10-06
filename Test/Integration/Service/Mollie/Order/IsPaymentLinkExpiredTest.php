<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Mollie\Order\IsPaymentLinkExpired;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class IsPaymentLinkExpiredTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testIsValidOnTheDayBeforeTheDefaultExpiry(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P27D');

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->execute($order);

        $this->assertFalse($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testIsExpiredAfterTheDefaultExpiry(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P28DT1H');

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->execute($order);

        $this->assertTrue($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 10
     */
    public function testIsValidWithinTheConfiguredDays(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P9D');

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->execute($order);

        $this->assertFalse($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 10
     */
    public function testIsExpiredAfterTheConfiguredDays(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P10DT1H');

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->execute($order);

        $this->assertTrue($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 0
     */
    public function testUsesTheDefaultExpiryWhenTheConfiguredDaysAreInvalid(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P27D');

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->execute($order);

        $this->assertFalse($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire 1
     */
    public function testIgnoresTheExpirySettingOfALimitedMethod(): void
    {
        $order = $this->createPaymentLinkOrderCreatedAgo('P5D');
        $order->getPayment()->setAdditionalInformation(['limited_methods' => ['ideal']]);

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->execute($order);

        $this->assertFalse($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 10
     */
    public function testReturnsTheCreationDatePlusTheConfiguredDaysAsExpiryDate(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setCreatedAt('2026-01-01 12:00:00');

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->getExpiresAt($order);

        $this->assertSame('2026-01-11 12:00:00', $result->format('Y-m-d H:i:s'));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/days_before_expire 10
     */
    public function testReturnsTheConfiguredDaysAgoAsLatestExpiredCreationDate(): void
    {
        $expected = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->sub(new DateInterval('P10D'));

        $result = $this->objectManager->create(IsPaymentLinkExpired::class)->getLatestExpiredCreationDate(1);

        $this->assertEqualsWithDelta($expected->getTimestamp(), $result->getTimestamp(), 5);
        $this->assertSame('UTC', $result->getTimezone()->getName());
    }

    private function createPaymentLinkOrderCreatedAgo(string $interval): OrderInterface
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Paymentlink::CODE);
        $order->setCreatedAt(
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->sub(new DateInterval($interval))
                ->format('Y-m-d H:i:s'),
        );

        return $order;
    }
}
