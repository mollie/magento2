<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Block\Info;

use Magento\Sales\Model\Order\Payment\Info;
use Mollie\Payment\Block\Info\Base;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BaseTest extends IntegrationTestCase
{
    public function testReturnsTheDashboardUrl(): void
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('dashboard_url', 'http://example.com/dashboard');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('http://example.com/dashboard', $instance->getDashboardUrl());
    }

    public function testReturnsTheMollieId(): void
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('mollie_id', 'ord_123abc');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('ord_123abc', $instance->getMollieId());
    }

    public function returnsNullWhenInfoIsNotAvailable(): array
    {
        return [
            ['getDashboardUrl'],
            ['getMollieId'],
        ];
    }

    /**
     * @dataProvider returnsNullWhenInfoIsNotAvailable
     */
    public function testReturnsNullWhenInfoIsNotAvailable(string $method): void
    {
        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $this->assertNull($instance->{$method}());
    }

    public function testReturnsTheRemainderAmount(): void
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('remainder_amount', '100');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('100', $instance->getRemainderAmount());
    }
}
