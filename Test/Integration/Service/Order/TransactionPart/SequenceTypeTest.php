<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPart\SequenceType;

class SequenceTypeTest extends IntegrationTestCase
{
    public function testDoesNothingWhenTheCartDoesNotContainARecurringProduct()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(false);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Payments::CHECKOUT_TYPE,
            ['empty' => true]
        );

        $this->assertEquals(['empty' => true], $result);
    }

    public function testIncludesTheSequenceTypeForThePaymentsApi()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Payments::CHECKOUT_TYPE,
            ['empty' => false]
        );

        $this->assertEquals(['empty' => false, 'sequenceType' => 'first'], $result);
    }

    public function testIncludesTheSequenceTypeForTheOrdersApi()
    {
        $orderContainsSubscriptionProductMock = $this->createMock(OrderContainsSubscriptionProduct::class);
        $orderContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var SequenceType $instance */
        $instance = $this->objectManager->create(SequenceType::class, [
            'orderContainsSubscriptionProduct' => $orderContainsSubscriptionProductMock,
        ]);
        $result = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Orders::CHECKOUT_TYPE,
            ['empty' => false, 'payment' => []]
        );

        $this->assertEquals(['empty' => false, 'payment' => ['sequenceType' => 'first']], $result);
    }
}
