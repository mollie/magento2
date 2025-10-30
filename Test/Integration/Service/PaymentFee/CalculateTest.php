<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\PaymentFee;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Model\Adminhtml\Source\PaymentFeeType;
use Mollie\Payment\Service\Config\PaymentFee;
use Mollie\Payment\Service\PaymentFee\Calculate;
use Mollie\Payment\Service\PaymentFee\MaximumSurcharge;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Service\PaymentFee\Types\FixedAmount;
use Mollie\Payment\Service\PaymentFee\Types\FixedAmountAndPercentage;
use Mollie\Payment\Service\PaymentFee\Types\Percentage;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CalculateTest extends IntegrationTestCase
{
    public function testReturnsEmptyResultWhenNotAvailable(): void
    {
        /** @var Calculate $instance */
        $instance = $this->objectManager->create(Calculate::class);

        $cart = $this->objectManager->create(CartInterface::class);
        $total = $this->objectManager->create(Total::class);

        $result = $instance->forCart($cart, $total);

        $this->assertEquals(0, $result->getAmount());
        $this->assertEquals(0, $result->getTaxAmount());
        $this->assertEquals(0, $result->getAmountIncludingTax());
    }

    public function calculatesTheFixedAmountProvider(): array
    {
        return [
            [PaymentFeeType::FIXED_FEE, 'fixedAmount', FixedAmount::class],
            [PaymentFeeType::PERCENTAGE, 'percentage', Percentage::class],
            [PaymentFeeType::FIXED_FEE_AND_PERCENTAGE, 'fixedAmountAndPercentage', FixedAmountAndPercentage::class],
        ];
    }

    /**
     * @dataProvider calculatesTheFixedAmountProvider
     */
    public function testCalculatesTheFixedAmount(string $type, string $key, string $typeCalculatorClass): void
    {
        /** @var Result $result */
        $result = $this->objectManager->create(Result::class);
        $result->setAmount(8.2645);
        $result->setTaxAmount(1.7355);

        $typeCalculator = $this->createMock($typeCalculatorClass);
        $typeCalculator->method('calculate')->willReturn($result);

        $configMock = $this->createMock(PaymentFee::class);
        $configMock->method('isAvailableForMethod')->willReturn(true);
        $configMock->method('getType')->willReturn($type);

        /** @var Calculate $instance */
        $instance = $this->objectManager->create(Calculate::class, [
            'config' => $configMock,
            $key => $typeCalculator,
        ]);

        $cart = $this->objectManager->create(CartInterface::class);
        $total = $this->objectManager->create(Total::class);

        $result = $instance->forCart($cart, $total);

        $this->assertEquals(8.2645, $result->getAmount());
        $this->assertEquals(1.7355, $result->getTaxAmount());
        $this->assertEquals(10, $result->getAmountIncludingTax());
    }

    public function testLimitsTheResult(): void
    {
        /** @var Result $result */
        $result = $this->objectManager->create(Result::class);
        $result->setAmount(16.5289256198);
        $result->setTaxAmount(3.4710743802);

        $percentageMock = $this->createMock(Percentage::class);
        $percentageMock->method('calculate')->willReturn($result);

        $configMock = $this->createMock(PaymentFee::class);
        $configMock->method('isAvailableForMethod')->willReturn(true);
        $configMock->method('getType')->willReturn(PaymentFeeType::PERCENTAGE);

        $maximumSurchageMock = $this->createMock(MaximumSurcharge::class);
        $maximumSurchageMock->method('calculate')->with(
            $this->isInstanceOf(CartInterface::class),
            $this->callback(function (Result $result): bool {
                $result->setAmount($result->getAmount() / 2);
                $result->setTaxAmount($result->getTaxAmount() / 2);

                return true;
            }),
        );

        /** @var Calculate $instance */
        $instance = $this->objectManager->create(Calculate::class, [
            'config' => $configMock,
            'percentage' => $percentageMock,
            'maximumSurcharge' => $maximumSurchageMock,
        ]);

        $cart = $this->objectManager->create(CartInterface::class);
        $total = $this->objectManager->create(Total::class);

        $result = $instance->forCart($cart, $total);

        $this->assertEquals(8.2644628099, $result->getAmount());
        $this->assertEquals(1.7355371901, $result->getTaxAmount());
        $this->assertEquals(10, $result->getAmountIncludingTax());
    }
}
