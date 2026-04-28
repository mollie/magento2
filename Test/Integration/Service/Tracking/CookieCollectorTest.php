<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Tracking;

use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\TestFramework\CookieManager as TestCookieManager;
use Mollie\Payment\Service\Tracking\CookieCollector;
use Mollie\Payment\Test\Fakes\Stdlib\FakeCookieManager;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CookieCollectorTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"}}
     */
    public function testForwardsRawCookieValue(): void
    {
        $cookies = $this->loadFakeCookieManager();
        $cookies->setCookies(['_ga' => 'GA1.2.123456789.987654321']);

        $collector = $this->objectManager->create(CookieCollector::class);
        $this->assertSame(['clientId' => 'GA1.2.123456789.987654321'], $collector->collect());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"},"_2":{"cookie_name":"_kbid","alias":"kbid"}}
     */
    public function testCollectsMultipleCookiesAndSkipsMissing(): void
    {
        $cookies = $this->loadFakeCookieManager();
        $cookies->setCookies(['_kbid' => 'abc-123']);

        $collector = $this->objectManager->create(CookieCollector::class);
        $this->assertSame(['kbid' => 'abc-123'], $collector->collect());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies
     */
    public function testReturnsEmptyArrayWhenConfigBlank(): void
    {
        $this->loadFakeCookieManager();

        $collector = $this->objectManager->create(CookieCollector::class);
        $this->assertSame([], $collector->collect());
    }

    private function loadFakeCookieManager(): FakeCookieManager
    {
        $instance = $this->objectManager->create(FakeCookieManager::class);
        $this->objectManager->addSharedInstance($instance, CookieManagerInterface::class);
        $this->objectManager->addSharedInstance($instance, PhpCookieManager::class);
        $this->objectManager->addSharedInstance($instance, TestCookieManager::class);

        return $instance;
    }
}
