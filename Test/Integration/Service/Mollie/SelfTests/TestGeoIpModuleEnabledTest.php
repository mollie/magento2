<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\SelfTests;

use Magento\Framework\Module\ModuleListInterface;
use Mollie\Payment\Service\Mollie\SelfTests\TestGeoIpModuleEnabled;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TestGeoIpModuleEnabledTest extends IntegrationTestCase
{
    public function testReturnsNoMessagesWhenNoGeoIpModuleIsEnabled(): void
    {
        $moduleList = $this->createMock(ModuleListInterface::class);
        $moduleList->method('getNames')->willReturn([
            'Magento_Catalog',
            'Magento_Sales',
            'Mollie_Payment',
        ]);

        $instance = new TestGeoIpModuleEnabled($moduleList);
        $instance->execute();

        $this->assertSame([], $instance->getMessages());
    }

    public function testReturnsWarningWhenAGeoIpModuleIsEnabled(): void
    {
        $moduleList = $this->createMock(ModuleListInterface::class);
        $moduleList->method('getNames')->willReturn([
            'Magento_Catalog',
            'Amasty_Geoip',
            'Mollie_Payment',
        ]);

        $instance = new TestGeoIpModuleEnabled($moduleList);
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('warning', $messages[0]['type']);
        $this->assertStringContainsString('Amasty_Geoip', $messages[0]['message']);
        $this->assertStringContainsString('exclude', $messages[0]['message']);
    }

    public function testReturnsSingleWarningWhenMultipleGeoIpModulesAreEnabled(): void
    {
        $moduleList = $this->createMock(ModuleListInterface::class);
        $moduleList->method('getNames')->willReturn([
            'Magento_Catalog',
            'Amasty_Geoip',
            'MaxMind_GeoIP',
            'Mollie_Payment',
        ]);

        $instance = new TestGeoIpModuleEnabled($moduleList);
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('warning', $messages[0]['type']);
        $this->assertStringContainsString('Amasty_Geoip', $messages[0]['message']);
        $this->assertStringContainsString('MaxMind_GeoIP', $messages[0]['message']);
    }

    public function testReturnsWarningWhenGeoIpModuleWithUnderscoreIsEnabled(): void
    {
        $moduleList = $this->createMock(ModuleListInterface::class);
        $moduleList->method('getNames')->willReturn([
            'Magento_Catalog',
            'Vendor_Geo_Ip',
            'Mollie_Payment',
        ]);

        $instance = new TestGeoIpModuleEnabled($moduleList);
        $instance->execute();

        $messages = $instance->getMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('warning', $messages[0]['type']);
        $this->assertStringContainsString('Vendor_Geo_Ip', $messages[0]['message']);
    }
}
