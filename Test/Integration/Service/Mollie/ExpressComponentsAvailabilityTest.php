<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\ExpressComponentsAvailability;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ExpressComponentsAvailabilityTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     */
    public function testIsAvailableWhenModuleAndMethodAreEnabled(): void
    {
        $instance = $this->objectManager->create(ExpressComponentsAvailability::class);

        $result = $instance->isAvailable();

        $this->assertTrue($result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/enabled 1
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 0
     */
    public function testIsNotAvailableWhenMethodIsDisabled(): void
    {
        $instance = $this->objectManager->create(ExpressComponentsAvailability::class);

        $result = $instance->isAvailable();

        $this->assertFalse($result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/enabled 0
     * @magentoConfigFixture current_store payment/mollie_methods_expresscomponents/active 1
     */
    public function testIsNotAvailableWhenModuleIsDisabled(): void
    {
        $instance = $this->objectManager->create(ExpressComponentsAvailability::class);

        $result = $instance->isAvailable();

        $this->assertFalse($result);
    }
}
