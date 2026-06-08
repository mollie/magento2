<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Service\Order\StartTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StartTransactionTest extends IntegrationTestCase
{
    public function testDoesNothingWhenTheOrderDoesNotExists(): void
    {
        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class);

        $result = $instance->byIncrementId('dummyvalue');

        $this->assertNull($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenThePaymentIsNotAMolliePayment(): void
    {
        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class);

        $result = $instance->byIncrementId('100000001');

        $this->assertNull($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testStartsTheTransaction(): void
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod(Ideal::CODE);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $startTransactionServiceMock = $this->createMock(\Mollie\Payment\Service\Mollie\StartTransaction::class);
        $startTransactionServiceMock->expects($this->once())->method('execute');

        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class, [
            'startTransaction' => $startTransactionServiceMock,
        ]);

        $instance->byIncrementId('100000001');
    }
}
