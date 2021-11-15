<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;

class MolliePaymentBuilder
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var MollieApiClient
     */
    private $client;

    public function __construct()
    {
        $this->client = new MollieApiClient();
        $this->payment = new Payment($this->client);
    }

    public function setAmount(float $value, $currency = 'EUR'): void
    {
        $this->payment->amount = new \StdClass();
        $this->payment->amount->value = $value;
        $this->payment->amount->currency = $currency;
    }

    public function build(): Payment
    {
        return $this->payment;
    }
}
