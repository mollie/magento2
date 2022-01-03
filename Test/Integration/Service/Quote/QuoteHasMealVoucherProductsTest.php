<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class QuoteHasMealVoucherProductsTest extends IntegrationTestCase
{
    public function testGetItemsCanReturnNull()
    {
        $cartMock = $this->createMock(CartInterface::class);
        $cartMock->method('getItems')->willReturn(null);

        /** @var QuoteHasMealVoucherProducts $instance */
        $instance = $this->objectManager->get(QuoteHasMealVoucherProducts::class);

        $result = $instance->check($cartMock);

        $this->assertEquals(0, $result);
    }
}
