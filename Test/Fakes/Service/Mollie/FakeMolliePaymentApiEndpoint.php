<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Resources\Payment;

class FakeMolliePaymentApiEndpoint extends PaymentEndpoint
{
    /** @var Payment[] */
    private $fakePayments = [];

    public function setFakePayment(Payment $payment): void
    {
        $this->fakePayments[$payment->id] = $payment;
    }

    public function get($paymentId, array $parameters = [])
    {
        if (!$this->fakePayments) {
            throw new \Exception('No fake payment set. Aborting');
        }

        return $this->fakePayments[$paymentId];
    }
}
