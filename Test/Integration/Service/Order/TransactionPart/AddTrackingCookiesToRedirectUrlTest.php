<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\TestFramework\CookieManager as TestCookieManager;
use Mollie\Payment\Service\Order\TransactionPart\AddTrackingCookiesToRedirectUrl;
use Mollie\Payment\Test\Fakes\Stdlib\FakeCookieManager;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AddTrackingCookiesToRedirectUrlTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"}}
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAppendsClientIdAsFirstQueryParam(): void
    {
        $cookies = $this->loadFakeCookieManager();
        $cookies->setCookies(['_ga' => 'GA1.2.111.222']);

        $order = $this->loadOrder('100000001');

        $instance = $this->objectManager->create(AddTrackingCookiesToRedirectUrl::class);
        $result = $instance->process($order, ['redirectUrl' => 'https://shop.example/success']);

        $this->assertSame(
            'https://shop.example/success?clientId=GA1.2.111.222',
            $result['redirectUrl'],
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"},"_2":{"cookie_name":"_kbid","alias":"kbid"}}
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAppendsAllAliasesUsingCorrectSeparators(): void
    {
        $cookies = $this->loadFakeCookieManager();
        $cookies->setCookies([
            '_ga' => 'GA1.2.111.222',
            '_kbid' => 'kb-token',
        ]);

        $order = $this->loadOrder('100000001');

        $instance = $this->objectManager->create(AddTrackingCookiesToRedirectUrl::class);
        $result = $instance->process(
            $order,
            ['redirectUrl' => 'https://shop.example/success?utm_source=email'],
        );

        $this->assertSame(
            'https://shop.example/success?utm_source=email&clientId=GA1.2.111.222&kbid=kb-token',
            $result['redirectUrl'],
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"}}
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testLeavesTransactionUnchangedWhenNoCookies(): void
    {
        $this->loadFakeCookieManager();

        $order = $this->loadOrder('100000001');

        $instance = $this->objectManager->create(AddTrackingCookiesToRedirectUrl::class);
        $result = $instance->process($order, ['redirectUrl' => 'https://shop.example/success']);

        $this->assertSame('https://shop.example/success', $result['redirectUrl']);
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
