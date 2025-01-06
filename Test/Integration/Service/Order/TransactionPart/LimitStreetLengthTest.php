<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Service\Order\TransactionPart\LimitStreetLength;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LimitStreetLengthTest extends IntegrationTestCase
{
    public function testLimitsTheAddressForOrdersApi(): void
    {
        /** @var LimitStreetLength $instance */
        $instance = $this->objectManager->create(LimitStreetLength::class);

        $transaction = [
            'billingAddress' => [
                'streetAndNumber' => 'a super long steet name that exceeds the maximum of 100 characters and should be truncated when its too long',
            ],
            'shippingAddress' => [
                'streetAndNumber' => 'a super long steet name that exceeds the maximum of 100 characters and should be truncated when its too long',
            ],
        ];

        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Orders::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertEquals(
            'a super long steet name that exceeds the maximum of 100 characters and should be truncated when its ',
            $result['billingAddress']['streetAndNumber']
        );

        $this->assertEquals(
            'a super long steet name that exceeds the maximum of 100 characters and should be truncated when its ',
            $result['shippingAddress']['streetAndNumber']
        );

        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('street_truncated', $result['metadata']);
        $this->assertTrue($result['metadata']['street_truncated']);
    }

    public function testHandlesVirtualProducts(): void
    {
        /** @var LimitStreetLength $instance */
        $instance = $this->objectManager->create(LimitStreetLength::class);

        $transaction = [
            'billingAddress' => [
                'streetAndNumber' => 'a super long steet name that exceeds the maximum of 100 characters and should be truncated when its too long',
            ],
            // Omit the shipping address as that's not relevant for virtual products
        ];

        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Orders::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertEquals(
            'a super long steet name that exceeds the maximum of 100 characters and should be truncated when its ',
            $result['billingAddress']['streetAndNumber']
        );

        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('street_truncated', $result['metadata']);
        $this->assertTrue($result['metadata']['street_truncated']);
    }

    public function testDoesNotMarkAsTruncatedWhenNotTruncated(): void
    {
        /** @var LimitStreetLength $instance */
        $instance = $this->objectManager->create(LimitStreetLength::class);

        $transaction = [
            'metadata' => [],
            'billingAddress' => [
                'streetAndNumber' => 'a short street name',
            ],
        ];

        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Orders::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertEquals(
            'a short street name',
            $result['billingAddress']['streetAndNumber']
        );

        $this->assertArrayNotHasKey('street_truncated', $result['metadata']);
    }
}
