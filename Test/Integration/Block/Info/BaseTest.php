<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Block\Info;

use Magento\Sales\Model\Order\Payment\Info;
use Mollie\Payment\Block\Info\Base;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BaseTest extends IntegrationTestCase
{
    public function testReturnsTheDashboardUrl()
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('dashboard_url', 'http://example.com/dashboard');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('http://example.com/dashboard', $instance->getDashboardUrl());
    }

    public function testReturnsTheMollieId()
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('mollie_id', 'ord_123abc');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('ord_123abc', $instance->getMollieId());
    }

    public function returnsFalseWhenInfoIsNotAvailable()
    {
        return [
            ['getDashboardUrl'],
            ['getMollieId']
        ];
    }

    /**
     * @dataProvider returnsFalseWhenInfoIsNotAvailable
     */
    public function testReturnsFalseWhenInfoIsNotAvailable($method)
    {
        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $this->assertFalse($instance->{$method}());
    }
}