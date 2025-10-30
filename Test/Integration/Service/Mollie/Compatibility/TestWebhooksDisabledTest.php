<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Compatibility;

use Mollie\Payment\Service\Mollie\SelfTests\TestWebhooksDisabled;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TestWebhooksDisabledTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_general/type live
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks disabled
     */
    public function testReturnsNoErrorsWhenInLiveMode(): void
    {
        /** @var TestWebhooksDisabled $instance */
        $instance = $this->objectManager->get(TestWebhooksDisabled::class);
        $instance->execute();

        $this->assertCount(0, $instance->getMessages());
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks custom_url
     * @magentoConfigFixture current_store payment/mollie_general/webhook_custom_url random_value_for_test
     */
    public function testReturnsErrorWhenUsingCustomUrlAndTestModeEnabled(): void
    {
        /** @var TestWebhooksDisabled $instance */
        $instance = $this->objectManager->get(TestWebhooksDisabled::class);
        $instance->execute();

        $this->assertCount(1, $instance->getMessages());
    }
}
