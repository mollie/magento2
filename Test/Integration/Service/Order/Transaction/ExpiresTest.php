<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Transaction;

use DateInterval;
use DateTimeImmutable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ExpiresTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire
     */
    public function testReturnsFalseWhenNoExpireIsSet(): void
    {
        /** @var Expires $instance */
        $instance = $this->objectManager->create(Expires::class);

        $this->assertFalse($instance->availableForMethod('ideal'));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire 5
     */
    public function testReturnsTrueWhenNoExpireIsSet(): void
    {
        /** @var Expires $instance */
        $instance = $this->objectManager->create(Expires::class);

        $this->assertTrue($instance->availableForMethod('ideal'));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/days_before_expire 5
     */
    public function testReturnsTheCorrectDate(): void
    {
        $now = $this->objectManager->create(TimezoneInterface::class)->scopeDate(null);
        $expected = $now->add(new DateInterval('P5D'));

        /** @var Expires $instance */
        $instance = $this->objectManager->create(Expires::class);

        $this->assertEquals($expected->format('Y-m-d'), $instance->atDateForMethod('ideal'));
    }

    public function testIsAvailableWhenSetInTheRequest(): void
    {
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);

        $request->setParams([
            'payment' => ['days_before_expire' => 10],
        ]);

        /** @var Expires $instance */
        $instance = $this->objectManager->create(Expires::class);

        $this->assertTrue($instance->availableForMethod());
    }

    public function testUsesTheValueInTheRequest(): void
    {
        /** @var RequestInterface $request */
        $request = $this->objectManager->get(RequestInterface::class);

        $now = new DateTimeImmutable('now');
        $expected = $now->add(new DateInterval('P10D'));

        $request->setParams([
            'payment' => ['days_before_expire' => $expected->format('Y-m-d')],
        ]);

        /** @var Expires $instance */
        $instance = $this->objectManager->create(Expires::class);

        $this->assertEquals($expected->format('Y-m-d'), $instance->atDateForMethod());
    }
}
