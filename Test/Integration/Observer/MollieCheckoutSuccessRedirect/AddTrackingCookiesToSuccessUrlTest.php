<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\MollieCheckoutSuccessRedirect;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Mollie\Payment\Observer\MollieCheckoutSuccessRedirect\AddTrackingCookiesToSuccessUrl;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AddTrackingCookiesToSuccessUrlTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"},"_2":{"cookie_name":"_kbid","alias":"kbid"}}
     */
    public function testCopiesAllConfiguredAliasesFromRequestToRedirectQuery(): void
    {
        /** @var Http $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParam('clientId', '111.222');
        $request->setParam('kbid', 'kb-token');

        $redirect = new DataObject();
        $observer = new Observer(['redirect' => $redirect]);

        $instance = $this->objectManager->create(AddTrackingCookiesToSuccessUrl::class);
        $instance->execute($observer);

        $this->assertSame(
            ['clientId' => '111.222', 'kbid' => 'kb-token'],
            $redirect->getData('query'),
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/tracking_cookies {"_1":{"cookie_name":"_ga","alias":"clientId"}}
     */
    public function testSkipsAliasesAbsentFromRequest(): void
    {
        /** @var Http $request */
        $request = $this->objectManager->get(RequestInterface::class);
        $request->clearParams();

        $redirect = new DataObject(['query' => ['existing' => 'value']]);
        $observer = new Observer(['redirect' => $redirect]);

        $instance = $this->objectManager->create(AddTrackingCookiesToSuccessUrl::class);
        $instance->execute($observer);

        $this->assertSame(['existing' => 'value'], $redirect->getData('query'));
    }
}
