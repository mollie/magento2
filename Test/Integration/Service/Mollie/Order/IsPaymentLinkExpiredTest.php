<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Mollie\Order\IsPaymentLinkExpired;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class IsPaymentLinkExpiredTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testIsValidTheDayBeforeTheDefaultExpire(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Paymentlink::CODE);

        $date = new \DateTimeImmutable();
        $date = $date->add(new \DateInterval('P28D'))->setTime(0, 0, 0);
        $order->setcreatedAt($date->format('Y-m-d H:i:s'));

        $instance = $this->objectManager->create(IsPaymentLinkExpired::class);

        $this->assertFalse($instance->execute($order));
    }
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testIsInvalidTheDayAfterTheDefaultExpire(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Paymentlink::CODE);

        $date = new \DateTimeImmutable();
        $date = $date->add(new \DateInterval('P29D'))->setTime(23, 59, 59);
        $order->setcreatedAt($date->format('Y-m-d H:i:s'));

        $instance = $this->objectManager->create(IsPaymentLinkExpired::class);

        $this->assertTrue($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire 10
     * @return void
     */
    public function testIsValidWhenAvailableForMethodIsSetTheDayBefore(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Paymentlink::CODE);

        $date = new \DateTimeImmutable();
        $date = $date->add(new \DateInterval('P9D'))->setTime(0, 0, 0);
        $order->setcreatedAt($date->format('Y-m-d H:i:s'));

        $order->getPayment()->setAdditionalInformation(['limited_methods' => ['ideal']]);

        $instance = $this->objectManager->create(IsPaymentLinkExpired::class);

        $this->assertFalse($instance->execute($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire 10
     * @return void
     */
    public function testIsValidWhenAvailableForMethodIsSetTheDayAfter(): void
    {
        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod(Paymentlink::CODE);

        $date = new \DateTimeImmutable();
        $date = $date->add(new \DateInterval('P11D'))->setTime(23, 59, 59);
        $order->setcreatedAt($date->format('Y-m-d H:i:s'));

        $order->getPayment()->setAdditionalInformation(['limited_methods' => ['ideal']]);

        $instance = $this->objectManager->create(IsPaymentLinkExpired::class);

        $this->assertTrue($instance->execute($order));
    }
}
