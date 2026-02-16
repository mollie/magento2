<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPart\SequenceType;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SequenceTypeTest extends IntegrationTestCase
{
    public function testDoesNothingWhenTheCartDoesNotContainARecurringProduct(): void
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(false);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            ['empty' => true],
        );

        $this->assertEquals(['empty' => true], $result);
    }

    public function testIncludesTheSequenceTypeForThePaymentsApi(): void
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            ['empty' => false],
        );

        $this->assertEquals(['empty' => false, 'sequenceType' => 'first'], $result);
    }
}
