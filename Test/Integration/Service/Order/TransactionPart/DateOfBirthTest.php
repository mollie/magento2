<?php

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPart\DateOfBirth;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class DateOfBirthTest extends IntegrationTestCase
{
    public function testDoesNothingWhenPaymentsApiIsUsed(): void
    {
        $transaction = [];

        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        $newTransaction = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Payments::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertSame($transaction, $newTransaction);
    }

    public function testDoesNothingWhenCustomerDobIsNotSet(): void
    {
        $transaction = ['consumerDateOfBirth' => null];

        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        $newTransaction = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Orders::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertSame($transaction, $newTransaction);
    }

    public function testFormatsTheDateCorrect(): void
    {
        $transaction = ['consumerDateOfBirth' => null];

        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setCustomerDob('2016-11-19 00:00:00');

        $newTransaction = $instance->process(
            $order,
            Orders::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertSame(
            '2016-11-19',
            $newTransaction['consumerDateOfBirth']
        );
    }
}
