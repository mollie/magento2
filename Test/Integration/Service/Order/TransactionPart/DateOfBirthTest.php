<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Service\Order\TransactionPart\DateOfBirth;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class DateOfBirthTest extends IntegrationTestCase
{
    public function testDoesNothingWhenPaymentMethodIsNotIn3(): void
    {
        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_ideal');

        $order->setCustomerDob('2016-11-19 00:00:00');

        $transaction = $instance->process(
            $order,
            [],
        );

        $this->assertArrayNotHasKey('consumerDateOfBirth', $transaction);
    }

    public function testDoesNothingWhenPaymentsApiIsUsed(): void
    {
        $transaction = [];

        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        $order = $this->objectManager->create(OrderInterface::class);
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $newTransaction = $instance->process(
            $order,
            $transaction,
        );

        $this->assertSame($transaction, $newTransaction);
    }

    public function testDoesNothingWhenCustomerDobIsNotSet(): void
    {
        $transaction = [];

        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        $order = $this->objectManager->create(OrderInterface::class);
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $newTransaction = $instance->process(
            $order,
            $transaction,
        );

        $this->assertSame($transaction, $newTransaction);
    }

    public function testFormatsTheDateCorrect(): void
    {
        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $order->setCustomerDob('2016-11-19 00:00:00');

        $transaction = $instance->process(
            $order,
            [],
        );

        $this->assertSame(
            '2016-11-19',
            $transaction['consumerDateOfBirth'],
        );
    }

    public function testFormatsTheDateCorrectWhenNoTimeIsAvailable(): void
    {
        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $order->setCustomerDob('2016-11-19');

        $transaction = $instance->process(
            $order,
            [],
        );

        $this->assertSame(
            '2016-11-19',
            $transaction['consumerDateOfBirth'],
        );
    }

    public function testDoesNothingWhenTheDateCantBeParsed(): void
    {
        /** @var DateOfBirth $instance */
        $instance = $this->objectManager->create(DateOfBirth::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setCustomerDob('nope');

        $transaction = $instance->process(
            $order,
            [],
        );

        $this->assertArrayNotHasKey(
            'consumerDateOfBirth',
            $transaction,
        );
    }
}
