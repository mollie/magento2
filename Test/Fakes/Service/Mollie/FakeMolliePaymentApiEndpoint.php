<?php

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Resources\Payment;

class FakeMolliePaymentApiEndpoint extends PaymentEndpoint
{
    /** @var Payment */
    private $fakePayment;

    public function setFakePayment(Payment $payment): void
    {
        $this->fakePayment = $payment;
    }

    public function get($paymentId, array $parameters = [])
    {
        if (!$this->fakePayment) {
            throw new \Exception('No fake payment set. Aborting');
        }

        return $this->fakePayment;
    }
}
