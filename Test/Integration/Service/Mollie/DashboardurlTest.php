<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\DashboardUrl;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class DashboardurlTest extends IntegrationTestCase
{
    public function testReturnsTheUrlForTheOrdersApi()
    {
        /** @var DashboardUrl $instance */
        $instance = $this->objectManager->create(DashboardUrl::class);

        $this->assertEquals(
            'https://www.mollie.com/dashboard/orders/ord_123abc',
            $instance->forOrdersApi('default', 'ord_123abc')
        );
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/dashboard_url_orders_api https://www.example.com/{id}
     */
    public function testReturnsTheUrlForTheOrdersApiWithCustomUrl()
    {
        /** @var DashboardUrl $instance */
        $instance = $this->objectManager->create(DashboardUrl::class);

        $this->assertEquals(
            'https://www.example.com/ord_123abc',
            $instance->forOrdersApi('default', 'ord_123abc')
        );
    }
    public function testReturnsTheUrlForThePaymentsApi()
    {
        /** @var DashboardUrl $instance */
        $instance = $this->objectManager->create(DashboardUrl::class);

        $this->assertEquals(
            'https://www.mollie.com/dashboard/payments/ord_123abc',
            $instance->forPaymentsApi('default', 'ord_123abc')
        );
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/dashboard_url_payments_api https://www.example.com/{id}
     */
    public function testReturnsTheUrlForThePaymentsApiWithCustomUrl()
    {
        /** @var DashboardUrl $instance */
        $instance = $this->objectManager->create(DashboardUrl::class);

        $this->assertEquals(
            'https://www.example.com/ord_123abc',
            $instance->forPaymentsApi('default', 'ord_123abc')
        );
    }
}