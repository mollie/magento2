<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\PaymentFailure;
use Mollie\Payment\Service\Mollie\PaymentFailureReason;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFailureTest extends IntegrationTestCase
{
    public function testReadsTheReasonAndMessageFromThePaymentDetails(): void
    {
        $result = PaymentFailure::fromPaymentDetails((object) [
            'failureReason' => 'insufficient_funds',
            'failureMessage' => 'Your card has insufficient funds.',
        ]);

        $this->assertSame(PaymentFailureReason::InsufficientFunds, $result->getReason());
        $this->assertSame('Your card has insufficient funds.', $result->getMessage());
    }

    public function testHasNoReasonOrMessageWhenThereAreNoDetails(): void
    {
        $result = PaymentFailure::fromPaymentDetails(null);

        $this->assertNull($result->getReason());
        $this->assertNull($result->getMessage());
    }

    public function testIgnoresUnknownReasonsAndEmptyMessages(): void
    {
        $result = PaymentFailure::fromPaymentDetails((object) [
            'failureReason' => 'a_reason_mollie_added_later',
            'failureMessage' => '',
        ]);

        $this->assertNull($result->getReason());
        $this->assertNull($result->getMessage());
    }

    public function testDetectsPossibleFraud(): void
    {
        $result = PaymentFailure::fromPaymentDetails((object) ['failureReason' => 'possible_fraud']);

        $this->assertTrue($result->isPossibleFraud());
    }
}
