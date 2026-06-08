<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class QuoteHasMealVoucherProductsTest extends IntegrationTestCase
{
    public function testGetItemsCanReturnNull(): void
    {
        $cartMock = $this->createMock(CartInterface::class);
        $cartMock->method('getItems')->willReturn(null);

        /** @var QuoteHasMealVoucherProducts $instance */
        $instance = $this->objectManager->get(QuoteHasMealVoucherProducts::class);

        $result = $instance->check($cartMock);

        $this->assertEquals(0, $result);
    }
}
