<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TransactionTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 0
     */
    public function testRedirectUrlWithoutCustomUrl()
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        $result = $instance->getRedirectUrl(9999, 'paymenttoken');

        $this->assertContains('mollie/checkout/process', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url
     */
    public function testRedirectUrlWithEmptyCustomUrl()
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        $result = $instance->getRedirectUrl(9999, 'paymenttoken');

        $this->assertContains('mollie/checkout/process', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url https://www.mollie.com
     */
    public function testRedirectUrlWithFilledCustomUrl()
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        $result = $instance->getRedirectUrl(9999, 'paymenttoken');

        $this->assertContains('https://www.mollie.com', $result);
    }
}
