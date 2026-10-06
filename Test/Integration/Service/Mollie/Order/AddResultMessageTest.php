<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Framework\Message\ManagerInterface;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\Order\AddResultMessage;
use Mollie\Payment\Service\Mollie\PaymentFailure;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class AddResultMessageTest extends IntegrationTestCase
{
    public function testShowsTheFailureMessageFromMollie(): void
    {
        $result = $this->createStatusResult('failed', PaymentFailure::fromPaymentDetails((object) [
            'failureReason' => 'insufficient_funds',
            'failureMessage' => 'Your card has insufficient funds.',
        ]));

        $this->objectManager->create(AddResultMessage::class)->execute($result);

        $this->assertSame('Your card has insufficient funds.', $this->getLastMessage());
    }

    public function testShowsANeutralMessageForPossibleFraud(): void
    {
        $result = $this->createStatusResult('failed', PaymentFailure::fromPaymentDetails((object) [
            'failureReason' => 'possible_fraud',
            'failureMessage' => 'The payment was declined due to suspected fraud.',
        ]));

        $this->objectManager->create(AddResultMessage::class)->execute($result);

        $this->assertSame('Your payment was declined. Please select another payment method.', $this->getLastMessage());
    }

    public function testShowsTheGenericMessageWhenThereIsNoFailureMessage(): void
    {
        $result = $this->createStatusResult('failed', PaymentFailure::fromPaymentDetails(null));

        $this->objectManager->create(AddResultMessage::class)->execute($result);

        $this->assertSame(
            'Transaction failed. Please verify your billing information and payment method, and try again.',
            $this->getLastMessage()
        );
    }

    public function testShowsTheCanceledMessageForCanceledPayments(): void
    {
        $result = $this->createStatusResult('canceled', null);

        $this->objectManager->create(AddResultMessage::class)->execute($result);

        $this->assertSame('Payment canceled, please try again.', $this->getLastMessage());
    }

    private function createStatusResult(string $status, ?PaymentFailure $paymentFailure): GetMollieStatusResult
    {
        return $this->objectManager->create(GetMollieStatusResult::class, [
            'status' => $status,
            'method' => 'creditcard',
            'paymentFailure' => $paymentFailure,
        ]);
    }

    private function getLastMessage(): string
    {
        return (string) $this->objectManager->get(ManagerInterface::class)
            ->getMessages(true)
            ->getLastAddedMessage()
            ->getText();
    }
}
