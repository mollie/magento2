<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Compatibility;

use Magento\Framework\ObjectManagerInterface;
use Mollie\Payment\Service\Mollie\SelfTests\TestExtensionAttributes;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TestExtensionAttributesTest extends IntegrationTestCase
{
    public function testDoesNotHaveErrorsWhenAllEnabled(): void
    {
        $fakeClass = new class() {
            public function getExtensionAttributes(): object
            {
                return new class() {
                    public function getMolliePaymentFee(): void
                    {
                    }
                    public function getBaseMolliePaymentFee(): void
                    {
                    }
                    public function getMolliePaymentFeeTax(): void
                    {
                    }
                    public function getBaseMolliePaymentFeeTax(): void
                    {
                    }
                    public function getMollieCustomerId(): void
                    {
                    }
                    public function getMollieRecurringType(): void
                    {
                    }
                    public function getMollieRecurringData(): void
                    {
                    }
                };
            }
        };

        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $objectManagerMock->method('get')->willReturn($fakeClass);

        /** @var TestExtensionAttributes $instance */
        $instance = $this->objectManager->create(TestExtensionAttributes::class, [
            'objectManager' => $objectManagerMock,
        ]);

        $instance->execute();

        $this->assertCount(0, $instance->getMessages());
    }

    public function testReturnsErrorWhenMethodsDoNotExists(): void
    {
        /**
         * We create a fake class that will not return any extension attributes, and fail because of that.
         */
        $fakeClass = new class() {
            public function getExtensionAttributes(): void
            {
            }
        };

        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $objectManagerMock->method('get')->willReturn($fakeClass);

        /** @var TestExtensionAttributes $instance */
        $instance = $this->objectManager->create(TestExtensionAttributes::class, [
            'objectManager' => $objectManagerMock,
        ]);

        $instance->execute();

        $this->assertCount(1, $instance->getMessages());
    }
}
