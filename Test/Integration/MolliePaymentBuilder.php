<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use StdClass;

class MolliePaymentBuilder
{
    private Payment $payment;

    private MollieApiClient $client;

    public function __construct()
    {
        $this->client = new MollieApiClient();
        $this->payment = new Payment($this->client);
    }

    public function setAmount(float $value, $currency = 'EUR'): void
    {
        $this->payment->amount = new StdClass();
        $this->payment->amount->value = $value;
        $this->payment->amount->currency = $currency;
    }

    public function setStatus(string $status): void
    {
        $this->payment->status = $status;
    }

    public function setMethod(string $string): void
    {
        $this->payment->method = $string;
    }

    public function build(): Payment
    {
        return $this->payment;
    }
}
