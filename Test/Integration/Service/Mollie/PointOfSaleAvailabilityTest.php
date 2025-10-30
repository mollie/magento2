<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Quote\Model\Quote;
use Mollie\Payment\Service\Mollie\PointOfSaleAvailability;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PointOfSaleAvailabilityTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @return void
     */
    public function testDoesNotAllowAccessWhenSettingIsNotSet(): void
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        /** @var PointOfSaleAvailability $instance */
        $instance = $this->objectManager->get(PointOfSaleAvailability::class);

        $this->assertFalse($instance->isAvailable($cart));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_pointofsale/allowed_customer_groups 0
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @return void
     */
    public function testAllowsNotLoggedInAccesWhenConfiguredProperly(): void
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        /** @var PointOfSaleAvailability $instance */
        $instance = $this->objectManager->get(PointOfSaleAvailability::class);

        $this->assertTrue($instance->isAvailable($cart));
    }
}
