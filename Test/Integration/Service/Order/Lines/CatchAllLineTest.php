<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\CatchAllLine;
use Mollie\Payment\Service\Order\Lines\ThirdPartyTotalCollectors;
use Mollie\Payment\Test\Fakes\Logger\MollieLoggerFake;
use Mollie\Payment\Test\Fakes\Sales\SalesConfigFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CatchAllLineTest extends IntegrationTestCase
{
    public function testCreatesASurchargeLineForAPositiveDifference(): void
    {
        $instance = $this->objectManager->create(CatchAllLine::class);

        $result = $instance->create($this->createOrder(), 'EUR', 5.0);

        $this->assertSame('surcharge', $result['type']);
        $this->assertSame('Additional fees', $result['description']);
        $this->assertSame(['currency' => 'EUR', 'value' => '5.00'], $result['totalAmount']);
        $this->assertSame(['currency' => 'EUR', 'value' => '5.00'], $result['unitPrice']);
    }

    public function testCreatesADiscountLineForANegativeDifference(): void
    {
        $instance = $this->objectManager->create(CatchAllLine::class);

        $result = $instance->create($this->createOrder(), 'EUR', -5.0);

        $this->assertSame('discount', $result['type']);
        $this->assertSame('Additional discount', $result['description']);
        $this->assertSame(['currency' => 'EUR', 'value' => '-5.00'], $result['totalAmount']);
    }

    public function testTheLineHasNoVat(): void
    {
        $instance = $this->objectManager->create(CatchAllLine::class);

        $result = $instance->create($this->createOrder(), 'EUR', 5.0);

        $this->assertSame('0.00', $result['vatRate']);
        $this->assertSame(['currency' => 'EUR', 'value' => '0.00'], $result['vatAmount']);
    }

    public function testLogsTheOrderTheDifferenceAndTheThirdPartyTotalCollectors(): void
    {
        $logger = $this->objectManager->create(MollieLoggerFake::class);
        $salesConfig = $this->objectManager->create(SalesConfigFake::class)
            ->withQuoteTotal('subtotal', 'Magento\Quote\Model\Quote\Address\Total\Subtotal')
            ->withQuoteTotal('mageworx_multifees', 'MageWorx\MultiFees\Model\Quote\Total\Fee');
        $instance = $this->objectManager->create(CatchAllLine::class, [
            'logger' => $logger,
            'thirdPartyTotalCollectors' => $this->objectManager->create(
                ThirdPartyTotalCollectors::class,
                ['salesConfig' => $salesConfig],
            ),
        ]);

        $instance->create($this->createOrder(), 'EUR', 5.0);

        $logs = $logger->getInfoLogs();
        $this->assertCount(1, $logs);
        $this->assertSame(
            [
                'increment_id' => '100000123',
                'difference' => 5.0,
                'third_party_total_collectors' => ['mageworx_multifees' => 'MageWorx\MultiFees\Model\Quote\Total\Fee'],
            ],
            $logs[0]['data'],
        );
    }

    private function createOrder(): OrderInterface
    {
        return $this->objectManager->create(OrderInterface::class)->setIncrementId('100000123');
    }
}
