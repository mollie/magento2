<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Payment\Helper\Data;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Order\StartTransaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class StartTransactionTest extends IntegrationTestCase
{
    public function testDoesNothingWhenTheOrderDoesNotExists()
    {
        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class);

        $result = $instance->byIncrementId('dummyvalue');

        $this->assertNull($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNothingWhenThePaymentIsNotAMolliePayment()
    {
        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class);

        $result = $instance->byIncrementId('100000001');

        $this->assertNull($result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testStartsTheTransaction()
    {
        $paymentMethodMock = $this->createMock(Mollie::class);
        $paymentMethodMock->expects($this->once())->method('startTransaction');

        $paymentHelperMock = $this->createMock(Data::class);
        $paymentHelperMock->method('getMethodInstance')->willReturn($paymentMethodMock);

        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class, [
            'paymentHelper' => $paymentHelperMock,
        ]);

        $instance->byIncrementId('100000001');
    }
}
