<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model;

use Mollie\Payment\Model\PaymentInstructionsConfigProvider;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentInstructionsConfigProviderTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_banktransfer/instructions Please pay before we ship.
     * @magentoConfigFixture default_store payment/mollie_methods_banktransfer/instructions_style info
     */
    public function testReturnsTheBanktransferInstructionsWithTheInfoStyle(): void
    {
        $instance = $this->objectManager->create(PaymentInstructionsConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertSame(
            ['payment' => ['mollie' => ['instructions' => [
                'mollie_methods_banktransfer' => ['html' => 'Please pay before we ship.', 'cssClass' => 'message info'],
            ]]]],
            $result,
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_multibanco/instructions Pay using the reference.
     * @magentoConfigFixture default_store payment/mollie_methods_multibanco/instructions_style warning
     */
    public function testReturnsTheMultibancoInstructionsWithTheConfiguredStyle(): void
    {
        $instance = $this->objectManager->create(PaymentInstructionsConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertSame(
            ['payment' => ['mollie' => ['instructions' => [
                'mollie_methods_multibanco' => ['html' => 'Pay using the reference.', 'cssClass' => 'message warning'],
            ]]]],
            $result,
        );
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_banktransfer/instructions Please pay before we ship.
     */
    public function testReturnsNothingWhenTheStyleIsNoInstructionsByDefault(): void
    {
        $instance = $this->objectManager->create(PaymentInstructionsConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertSame([], $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/instructions Should not be shown.
     */
    public function testIgnoresInstructionsOfMethodsThatDoNotSupportThem(): void
    {
        $instance = $this->objectManager->create(PaymentInstructionsConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertSame([], $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     */
    public function testReturnsNothingWhenNoInstructionsAreConfigured(): void
    {
        $instance = $this->objectManager->create(PaymentInstructionsConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertSame([], $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 0
     * @magentoConfigFixture default_store payment/mollie_methods_banktransfer/instructions Please pay before we ship.
     * @magentoConfigFixture default_store payment/mollie_methods_banktransfer/instructions_style info
     */
    public function testReturnsNothingWhenTheModuleIsDisabled(): void
    {
        $instance = $this->objectManager->create(PaymentInstructionsConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertSame([], $result);
    }
}
