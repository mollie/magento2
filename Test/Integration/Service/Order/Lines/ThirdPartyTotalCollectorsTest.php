<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Mollie\Payment\Service\Order\Lines\ThirdPartyTotalCollectors;
use Mollie\Payment\Test\Fakes\Sales\SalesConfigFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ThirdPartyTotalCollectorsTest extends IntegrationTestCase
{
    public function testReturnsOnlyCollectorsOutsideMagentoAndMollie(): void
    {
        $salesConfig = $this->objectManager->create(SalesConfigFake::class)
            ->withQuoteTotal('subtotal', 'Magento\Quote\Model\Quote\Address\Total\Subtotal')
            ->withQuoteTotal('mollie_payment_fee', 'Mollie\Payment\Model\PaymentFee\Quote\Address\Total\PaymentFee')
            ->withQuoteTotal('mageworx_multifees', 'MageWorx\MultiFees\Model\Quote\Total\Fee');
        $instance = $this->objectManager->create(ThirdPartyTotalCollectors::class, ['salesConfig' => $salesConfig]);

        $result = $instance->get();

        $this->assertSame(['mageworx_multifees' => 'MageWorx\MultiFees\Model\Quote\Total\Fee'], $result);
    }

    public function testIgnoresALeadingBackslashInTheClassName(): void
    {
        $salesConfig = $this->objectManager->create(SalesConfigFake::class)
            ->withQuoteTotal('discount', '\Magento\SalesRule\Model\Quote\Discount');
        $instance = $this->objectManager->create(ThirdPartyTotalCollectors::class, ['salesConfig' => $salesConfig]);

        $result = $instance->get();

        $this->assertSame([], $result);
    }

    public function testReturnsNothingForACleanInstallation(): void
    {
        $instance = $this->objectManager->create(ThirdPartyTotalCollectors::class);

        $result = $instance->get();

        $this->assertSame([], $result);
    }
}
