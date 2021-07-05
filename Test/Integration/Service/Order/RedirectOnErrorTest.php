<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Service\Order\RedirectOnError;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class RedirectOnErrorTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_checkout_shipping
     */
    public function testGeneratesTheCorrectUrlWhenRedirectedToShipping()
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/', $instance->getUrl());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_checkout_payment
     */
    public function testGeneratesTheCorrectUrlWhenRedirectedToPayment()
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/#payment', $instance->getUrl());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_cart
     */
    public function testGeneratesTheCorrectUrlWhenRedirectedToCart()
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/cart/', $instance->getUrl());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/redirect_when_transaction_fails_to invalid_value
     */
    public function testWhenTheSettingIsInvalidItRedirectsToCart()
    {
        /** @var RedirectOnError $instance */
        $instance = $this->objectManager->create(RedirectOnError::class);

        $this->assertStringEndsWith('checkout/cart/', $instance->getUrl());
    }
}
