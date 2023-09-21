<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\ApplePay;

use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SupportedNetworksTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 0
     * @return void
     */
    public function testDoesReturnAllNetworksWhenManualCaptureIsDisabled(): void
    {
        /** @var SupportedNetworks $instance */
        $instance = $this->objectManager->create(SupportedNetworks::class);

        $result = $instance->execute();
        foreach (['amex', 'masterCard', 'visa', 'maestro', 'vPay'] as $network) {
            $this->assertContains($network, $result);
        }
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enable_manual_capture 1
     * @return void
     */
    public function testDoesNotIncludeMaestroAndVpayWhenManualCaptureIsEnabled(): void
    {
        /** @var SupportedNetworks $instance */
        $instance = $this->objectManager->create(SupportedNetworks::class);

        $result = $instance->execute();
        foreach (['amex', 'masterCard', 'visa'] as $network) {
            $this->assertContains($network, $result);
        }

        foreach (['maestro', 'vPay'] as $network) {
            $this->assertNotContains($network, $result);
        }
    }
}
