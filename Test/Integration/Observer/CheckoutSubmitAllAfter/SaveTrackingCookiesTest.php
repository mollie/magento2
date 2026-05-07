<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\TestFramework\CookieManager as TestCookieManager;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Api\TrackingRepositoryInterface;
use Mollie\Payment\Observer\CheckoutSubmitAllAfter\SaveTrackingCookies;
use Mollie\Payment\Test\Fakes\Stdlib\FakeCookieManager;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SaveTrackingCookiesTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"},"_2":{"cookie_name":"_kbid","alias":"kbid"}}
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testPersistsCollectedCookies(): void
    {
        $cookies = $this->loadFakeCookieManager();
        $cookies->setCookies([
            '_ga' => 'GA1.2.111.222',
            '_kbid' => 'kb-token',
        ]);

        $quote = $this->objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        /** @var SaveTrackingCookies $observer */
        $observer = $this->objectManager->create(SaveTrackingCookies::class);
        $observer->execute(new Observer(['quote' => $quote]));

        /** @var TrackingRepositoryInterface $repository */
        $repository = $this->objectManager->create(TrackingRepositoryInterface::class);
        $data = $repository->getTrackingDataByCartId((int) $quote->getId());

        $this->assertSame([
            'clientId' => 'GA1.2.111.222',
            'kbid' => 'kb-token',
        ], $data);
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
