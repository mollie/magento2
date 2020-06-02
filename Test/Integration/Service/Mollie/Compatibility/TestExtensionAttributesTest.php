<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Mollie\Compatibility;

use Magento\Framework\ObjectManagerInterface;
use Mollie\Payment\Service\Mollie\Compatibility\TestExtensionAttributes;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TestExtensionAttributesTest extends IntegrationTestCase
{
    public function testDoesNotHaveErrorsWhenAllEnabled()
    {
        $fakeClass = new class {
            public function getExtensionAttributes() {
                return new class {
                    public function getMolliePaymentFee() {}
                    public function getBaseMolliePaymentFee() {}
                    public function getMolliePaymentFeeTax() {}
                    public function getBaseMolliePaymentFeeTax() {}
                    public function getMollieCustomerId() {}
                };
            }
        };

        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $objectManagerMock->method('get')->willReturn($fakeClass);

        /** @var TestExtensionAttributes $instance */
        $instance = $this->objectManager->create(TestExtensionAttributes::class, [
            'objectManager' => $objectManagerMock,
        ]);

        $result = $instance->execute([]);

        $this->assertCount(0, $result);
    }

    public function testReturnsErrorWhenMethodsDoNotExists()
    {
        /**
         * We create a fake class that will not return any extension attributes, and fail because of that.
         */
        $fakeClass = new class {
            public function getExtensionAttributes() {}
        };

        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $objectManagerMock->method('get')->willReturn($fakeClass);

        /** @var TestExtensionAttributes $instance */
        $instance = $this->objectManager->create(TestExtensionAttributes::class, [
            'objectManager' => $objectManagerMock,
        ]);

        $result = $instance->execute([]);

        $this->assertCount(1, $result);
    }
}