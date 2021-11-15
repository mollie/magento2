<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Api\Resources\Payment;

class MollieOrderBuilder
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var MollieApiClient
     */
    private $client;

    public function __construct()
    {
        $this->client = new MollieApiClient();
        $this->order = new Order($this->client);
    }

    public function addEmbedded(): void
    {
        if ($this->order->_embedded) {
            return;
        }

        $this->order->_embedded = new \StdClass;
    }

    public function setAmount(float $value, $currency = 'EUR'): void
    {
        $this->order->amount = new \StdClass();
        $this->order->amount->value = $value;
        $this->order->amount->currency = $currency;
    }

    public function addPayment(string $id): void
    {
        $this->addEmbedded();

        if (!isset($this->order->_embedded->payments)) {
            $this->order->_embedded->payments = [];
        }

        $payment = new Payment($this->client);
        $payment->id = $id;

        $this->order->_embedded->payments[] = $payment;
    }

    public function setStatus(string $status): void
    {
        $this->order->status = $status;
    }

    public function build(): Order
    {
        return $this->order;
    }
}
