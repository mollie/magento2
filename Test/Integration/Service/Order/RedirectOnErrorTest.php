<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Service\Order\RedirectOnError;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RedirectOnErrorTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_checkout_shipping
     */
    public function testGeneratesTheCorrectUrlWhenRedirectedToShipping(): void
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/', $instance->getUrl());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_checkout_payment
     */
    public function testGeneratesTheCorrectUrlWhenRedirectedToPayment(): void
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/#payment', $instance->getUrl());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_cart
     */
    public function testGeneratesTheCorrectUrlWhenRedirectedToCart(): void
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/cart/', $instance->getUrl());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to invalid_value
     */
    public function testWhenTheSettingIsInvalidItRedirectsToCart(): void
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/cart/', $instance->getUrl());
    }
}
