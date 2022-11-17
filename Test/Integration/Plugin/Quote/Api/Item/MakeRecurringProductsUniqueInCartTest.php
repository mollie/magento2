<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Plugin\Quote\Api\Item;

use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Payment\Plugin\Quote\Api\Item\MakeRecurringProductsUniqueInCart;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MakeRecurringProductsUniqueInCartTest extends IntegrationTestCase
{
    public function testDoesNotThrowAnErrorWhenNoBuyRequestIsAvailable(): void
    {
        /** @var MakeRecurringProductsUniqueInCart $instance */
        $instance = $this->objectManager->get(MakeRecurringProductsUniqueInCart::class);

        $result = rand(0, 1) === 1;
        $outcome = $instance->afterRepresentProduct(
            $this->objectManager->create(CartItemInterface::class),
            $result
        );

        $this->assertEquals($result, $outcome);
    }
}
