<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

use Magento\Framework\Module\Manager;
use Mollie\Payment\Service\Order\Lines\Generator\GeisswebEuvat;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GeisswebEuvatTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenTheModuleIsDisabled(): void
    {
        $order = $this->loadOrder('100000001');

        $moduleManagerMock = $this->createMock(Manager::class);
        $moduleManagerMock->method('isEnabled')->willReturn(false);

        /** @var GeisswebEuvat $instance */
        $instance = $this->objectManager->create(GeisswebEuvat::class, [
            'moduleManager' => $moduleManagerMock,
        ]);

        $result = $instance->process($order, [$this->buildOrderLine('90.00')]);

        $this->assertCount(1, $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenTheResidualIsZero(): void
    {
        $order = $this->loadOrder('100000001');
        $instance = $this->createInstanceWithModuleEnabled();

        $result = $instance->process($order, [$this->buildOrderLine('100.00')]);

        $this->assertCount(1, $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesSurchargeTypeWhenTheResidualIsPositive(): void
    {
        $order = $this->loadOrder('100000001');
        $instance = $this->createInstanceWithModuleEnabled();

        $result = $instance->process($order, [$this->buildOrderLine('90.00')]);
        $this->assertCount(2, $result);

        $balancingLine = end($result);
        $this->assertEquals('10.00', $balancingLine['unitPrice']['value']);
        $this->assertEquals('surcharge', $balancingLine['type']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesDiscountTypeWhenTheResidualIsNegative(): void
    {
        $order = $this->loadOrder('100000001');
        $instance = $this->createInstanceWithModuleEnabled();

        $result = $instance->process($order, [$this->buildOrderLine('110.00')]);
        $this->assertCount(2, $result);

        $balancingLine = end($result);
        $this->assertEquals('-10.00', $balancingLine['unitPrice']['value']);
        $this->assertEquals('discount', $balancingLine['type']);
    }

    private function createInstanceWithModuleEnabled(): GeisswebEuvat
    {
        $moduleManagerMock = $this->createMock(Manager::class);
        $moduleManagerMock->method('isEnabled')->willReturn(true);

        /** @var GeisswebEuvat $instance */
        $instance = $this->objectManager->create(GeisswebEuvat::class, [
            'moduleManager' => $moduleManagerMock,
        ]);

        return $instance;
    }

    private function buildOrderLine(string $totalAmount): array
    {
        return [
            'type' => 'physical',
            'description' => 'Simple Product',
            'quantity' => 1,
            'unitPrice' => ['currency' => 'USD', 'value' => $totalAmount],
            'totalAmount' => ['currency' => 'USD', 'value' => $totalAmount],
        ];
    }
}
