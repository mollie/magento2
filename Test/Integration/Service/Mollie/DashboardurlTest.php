<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\DashboardUrl;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class DashboardurlTest extends IntegrationTestCase
{
    public function testReturnsTheUrlForThePaymentsApi(): void
    {
        /** @var DashboardUrl $instance */
        $instance = $this->objectManager->create(DashboardUrl::class);

        $this->assertEquals(
            'https://my.mollie.com/dashboard/payments/ord_123abc',
            $instance->forPaymentsApi(1, 'ord_123abc'),
        );
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/dashboard_url_payments_api https://www.example.com/{id}
     */
    public function testReturnsTheUrlForThePaymentsApiWithCustomUrl(): void
    {
        /** @var DashboardUrl $instance */
        $instance = $this->objectManager->create(DashboardUrl::class);

        $this->assertEquals(
            'https://www.example.com/ord_123abc',
            $instance->forPaymentsApi(1, 'ord_123abc'),
        );
    }
}
